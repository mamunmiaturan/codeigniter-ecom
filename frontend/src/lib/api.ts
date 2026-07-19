import type { Product, ProductReview } from "@/types";

/**
 * Storefront API client for the CodeIgniter backend.
 *
 * The backend returns a `{ status, data }` envelope and a leaner product shape
 * than the frontend `Product` type. The mappers below adapt API responses into
 * the `Product`/`ProductReview` shapes the existing UI components expect,
 * filling sensible defaults for fields the catalog API does not provide
 * (colours, sizes, Bengali copy, bespoke flags, …).
 */

export const API_BASE =
  process.env.NEXT_PUBLIC_API_BASE ?? "https://ecom.test/api/v1";

// ---------------------------------------------------------------------------
// Raw API response shapes
// ---------------------------------------------------------------------------

interface ApiPricing {
  currency: string;
  price: string;
  special_price: string | null;
  effective_price: string;
  on_sale: boolean;
  discount_pct: number;
}

interface ApiStock {
  quantity: number;
  status: string;
}

interface ApiCategoryRef {
  name: string;
  slug: string;
}

export interface ApiCategory {
  id: number;
  name: string;
  slug: string;
  parent_id: number | null;
  icon: string | null;
  image: string | null;
  is_featured: boolean;
}

interface ApiProductList {
  id: number;
  name: string;
  slug: string;
  sku: string;
  product_type: string;
  thumbnail: string | null;
  pricing: ApiPricing;
  stock: ApiStock;
  category: ApiCategoryRef | null;
  brand: ApiCategoryRef | null;
  is_featured: boolean;
}

interface ApiProductImage {
  url: string | null;
  alt: string | null;
  is_primary: boolean;
}

interface ApiProductDetail extends ApiProductList {
  is_shippable: boolean;
  short_description: string | null;
  description: string | null;
  images: ApiProductImage[];
  attributes?: { code: string; name: string; value: string }[];
}

interface ApiReview {
  id: number;
  author: string;
  rating: number;
  title: string | null;
  comment: string | null;
  created_at: string;
  verified_purchase?: boolean;
}

interface ApiReviewResponse {
  summary: { count: number; average: number };
  reviews: ApiReview[];
}

interface Envelope<T> {
  status: "success" | "error";
  data: T;
  message?: string;
}

// ---------------------------------------------------------------------------
// Fetch helper
// ---------------------------------------------------------------------------

async function apiGet<T>(
  path: string,
  params?: Record<string, string | number | undefined>,
  signal?: AbortSignal,
  extraHeaders?: Record<string, string>,
): Promise<T> {
  const url = new URL(`${API_BASE}${path}`);
  if (params) {
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== "" && value !== "all") {
        url.searchParams.set(key, String(value));
      }
    }
  }

  const res = await fetch(url.toString(), {
    signal,
    headers: { Accept: "application/json", ...extraHeaders },
  });
  if (!res.ok) {
    throw new Error(`API ${res.status} for ${path}`);
  }
  const json = (await res.json()) as Envelope<T>;
  if (json.status !== "success") {
    throw new Error(json.message ?? `API error for ${path}`);
  }
  return json.data;
}

// ---------------------------------------------------------------------------
// Mappers: API → frontend `Product`
// ---------------------------------------------------------------------------

function initials(name: string): string {
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0]?.toUpperCase() ?? "")
    .join("");
}

function mapReview(r: ApiReview): ProductReview {
  return {
    id: String(r.id),
    name: r.author,
    avatar: initials(r.author),
    rating: r.rating,
    comment: r.title ? `${r.title} — ${r.comment ?? ""}` : (r.comment ?? ""),
    date: (r.created_at ?? "").slice(0, 10),
    verified: Boolean(r.verified_purchase),
  };
}

/** Shared pricing/flag mapping for both list and detail shapes. */
function baseFields(p: ApiProductList) {
  const price = parseFloat(p.pricing.effective_price);
  const original = parseFloat(p.pricing.price);
  return {
    id: String(p.id),
    slug: p.slug,
    name: p.name,
    nameBn: "",
    price,
    originalPrice: p.pricing.on_sale ? original : undefined,
    discount: p.pricing.on_sale ? p.pricing.discount_pct : undefined,
    category: p.category?.slug ?? "",
    shopCategory: p.category ? [p.category.slug] : [],
    colors: [],
    sizes: [],
    isBespoke: false,
    isNew: false,
    isBestseller: p.is_featured,
    inStock: p.stock.status === "in_stock" && p.stock.quantity > 0,
    tags: [p.category?.slug, p.brand?.slug].filter(Boolean) as string[],
  };
}

export function mapListProduct(p: ApiProductList): Product {
  return {
    ...baseFields(p),
    description: "",
    descriptionBn: "",
    images: p.thumbnail ? [p.thumbnail] : [],
    rating: 0,
    reviewCount: 0,
    reviews: [],
  };
}

export function mapDetailProduct(
  p: ApiProductDetail,
  reviewData?: ApiReviewResponse,
): Product {
  const images = p.images
    .map((img) => img.url)
    .filter((u): u is string => Boolean(u));
  if (images.length === 0 && p.thumbnail) images.push(p.thumbnail);

  return {
    ...baseFields(p),
    description: p.description ?? p.short_description ?? "",
    descriptionBn: "",
    images,
    rating: reviewData?.summary.average ?? 0,
    reviewCount: reviewData?.summary.count ?? 0,
    reviews: (reviewData?.reviews ?? []).map(mapReview),
    attributes: p.attributes ?? [],
  };
}

// ---------------------------------------------------------------------------
// Public API functions
// ---------------------------------------------------------------------------

export interface ProductFilters {
  category?: string;
  brand?: string;
  search?: string;
  min_price?: number;
  max_price?: number;
  featured?: boolean;
  sort?: string;
  page?: number;
  per_page?: number;
}

export async function fetchProducts(
  filters: ProductFilters = {},
  signal?: AbortSignal,
): Promise<Product[]> {
  const data = await apiGet<{ items: ApiProductList[] }>(
    "/products",
    {
      category: filters.category,
      brand: filters.brand,
      search: filters.search,
      min_price: filters.min_price,
      max_price: filters.max_price,
      featured: filters.featured ? "1" : undefined,
      sort: filters.sort,
      page: filters.page,
      per_page: filters.per_page ?? 48,
    },
    signal,
  );
  return data.items.map(mapListProduct);
}

export async function fetchFeaturedProducts(
  signal?: AbortSignal,
): Promise<Product[]> {
  return fetchProducts({ featured: true, per_page: 8 }, signal);
}

export async function fetchProduct(
  slug: string,
  signal?: AbortSignal,
): Promise<Product | null> {
  let detail: ApiProductDetail;
  try {
    detail = await apiGet<ApiProductDetail>(
      `/products/${encodeURIComponent(slug)}`,
      undefined,
      signal,
    );
  } catch {
    return null;
  }

  let reviews: ApiReviewResponse | undefined;
  try {
    reviews = await apiGet<ApiReviewResponse>(
      `/products/${encodeURIComponent(slug)}/reviews`,
      undefined,
      signal,
    );
  } catch {
    reviews = undefined;
  }

  return mapDetailProduct(detail, reviews);
}

export async function searchProducts(
  query: string,
  signal?: AbortSignal,
): Promise<Product[]> {
  return fetchProducts({ search: query, per_page: 5 }, signal);
}

export interface CategoryOption {
  id: string; // slug (or "all")
  label: string;
}

/** A category with its subcategories (built from the API's `tree`). */
export interface CategoryNode {
  id: string; // slug
  label: string;
  children: CategoryNode[];
}

interface ApiCategoryTreeNode extends ApiCategory {
  children?: ApiCategoryTreeNode[];
}

export async function fetchCategories(
  signal?: AbortSignal,
): Promise<CategoryNode[]> {
  const data = await apiGet<{ items: ApiCategory[]; tree: ApiCategoryTreeNode[] }>(
    "/categories",
    undefined,
    signal,
  );
  const toNode = (c: ApiCategoryTreeNode): CategoryNode => ({
    id: c.slug,
    label: c.name,
    children: (c.children ?? []).map(toNode),
  });
  return data.tree.map(toNode);
}

export interface BrandOption {
  id: string; // slug
  label: string;
}

export async function fetchBrands(signal?: AbortSignal): Promise<BrandOption[]> {
  const data = await apiGet<{ items: { name: string; slug: string }[] }>(
    "/brands",
    undefined,
    signal,
  );
  return data.items.map((b) => ({ id: b.slug, label: b.name }));
}

// ---------------------------------------------------------------------------
// Auth / customer account
// ---------------------------------------------------------------------------

export interface AuthTokens {
  token_type: string;
  access_token: string;
  expires_in: number;
  refresh_token: string;
  refresh_expires_at: number;
  scope: string;
}

export interface CustomerProfile {
  id: number;
  code: string;
  name: string;
  email: string;
  phone: string | null;
  gender: string | null;
  dob: string | null;
  address: string | null;
  status: string;
  last_login: string | null;
}

export interface RegisterInput {
  name: string;
  email: string;
  phone?: string;
  password: string;
}

/** POST helper that surfaces the backend's error `message` on failure. */
async function apiPost<T>(
  path: string,
  body: unknown,
  token?: string,
): Promise<T> {
  const headers: Record<string, string> = {
    "Content-Type": "application/json",
    Accept: "application/json",
  };
  if (token) headers.Authorization = `Bearer ${token}`;

  const res = await fetch(`${API_BASE}${path}`, {
    method: "POST",
    headers,
    body: JSON.stringify(body),
  });
  const json = (await res.json().catch(() => null)) as Envelope<T> | null;
  if (!res.ok || !json || json.status !== "success") {
    throw new Error(json?.message ?? `Request failed (${res.status})`);
  }
  return json.data;
}

/** Returns issued tokens, or `{ mfaRequired: true }` for 2FA accounts. */
export async function login(
  email: string,
  password: string,
): Promise<{ tokens?: AuthTokens; mfaRequired?: boolean }> {
  const data = await apiPost<AuthTokens & { mfa_required?: boolean }>(
    "/auth/login",
    { email, password },
  );
  if (data.mfa_required) return { mfaRequired: true };
  return { tokens: data };
}

export async function register(
  input: RegisterInput,
): Promise<{ customer: CustomerProfile; auth: AuthTokens }> {
  return apiPost<{ customer: CustomerProfile; auth: AuthTokens }>(
    "/customer/register",
    input,
  );
}

export async function getProfile(token: string): Promise<CustomerProfile> {
  return apiGet<CustomerProfile>("/customer/profile", undefined, undefined, {
    Authorization: `Bearer ${token}`,
  });
}

function authHeader(token: string) {
  return { Authorization: `Bearer ${token}` };
}

// ---------------------------------------------------------------------------
// Orders (customer bearer)
// ---------------------------------------------------------------------------

export interface OrderSummary {
  order_number: string;
  status: string;
  payment: { method: string | null; status: string | null };
  item_count: number;
  currency: string;
  total: string;
  placed_at: string;
}

export async function fetchOrders(token: string): Promise<OrderSummary[]> {
  const data = await apiGet<{ items: OrderSummary[] }>(
    "/orders",
    { per_page: 50 },
    undefined,
    authHeader(token),
  );
  return data.items;
}

// ---------------------------------------------------------------------------
// Wishlist (customer bearer)
// ---------------------------------------------------------------------------

export interface WishlistItem {
  product_id: number;
  name: string;
  slug: string;
  thumbnail: string | null;
  price: string;
  special_price: string | null;
  effective_price: string;
  currency: string;
  stock_status: string;
  added_at: string;
}

export async function fetchWishlist(token: string): Promise<WishlistItem[]> {
  const data = await apiGet<{ count: number; items: WishlistItem[] }>(
    "/wishlist",
    undefined,
    undefined,
    authHeader(token),
  );
  return data.items;
}

export async function removeWishlist(
  token: string,
  productId: number,
): Promise<void> {
  await apiPost("/wishlist/remove", { product_id: productId }, token);
}

// ---------------------------------------------------------------------------
// Saved addresses (customer bearer)
// ---------------------------------------------------------------------------

export interface Address {
  id: number;
  label: string | null;
  name: string;
  phone: string;
  division: string | null;
  district: string | null;
  area: string | null;
  address: string;
  landmark: string | null;
  postcode: string | null;
  is_default: boolean;
}

export interface AddressInput {
  label?: string;
  name: string;
  phone: string;
  division?: string;
  district?: string;
  area?: string;
  address: string;
  landmark?: string;
  postcode?: string;
  is_default?: boolean;
}

export async function fetchAddresses(token: string): Promise<Address[]> {
  const data = await apiGet<{ items: Address[] }>(
    "/customer/addresses",
    undefined,
    undefined,
    authHeader(token),
  );
  return data.items;
}

export async function createAddress(
  token: string,
  input: AddressInput,
): Promise<Address[]> {
  const data = await apiPost<{ items: Address[] }>(
    "/customer/addresses",
    input,
    token,
  );
  return data.items;
}

export async function updateAddress(
  token: string,
  id: number,
  input: AddressInput,
): Promise<Address[]> {
  const data = await apiPost<{ items: Address[] }>(
    "/customer/addresses/update",
    { id, ...input },
    token,
  );
  return data.items;
}

export async function deleteAddress(
  token: string,
  id: number,
): Promise<Address[]> {
  const data = await apiPost<{ items: Address[] }>(
    "/customer/addresses/delete",
    { id },
    token,
  );
  return data.items;
}

export async function setDefaultAddress(
  token: string,
  id: number,
): Promise<Address[]> {
  const data = await apiPost<{ items: Address[] }>(
    "/customer/addresses/default",
    { id },
    token,
  );
  return data.items;
}

// ---------------------------------------------------------------------------
// Shopping cart (guest via cart_token, or customer via bearer)
// ---------------------------------------------------------------------------

export interface CartLine {
  id: number;
  product: { id: number; name: string; slug: string; thumbnail: string | null };
  variant: { id: number; name: string } | null;
  quantity: number;
  unit_price: string;
  line_total: string;
  in_stock: boolean;
  available_qty: number;
}

export interface CartData {
  cart_id: number | null;
  item_count: number;
  currency: string;
  subtotal: string;
  discount: string;
  total: string;
  coupon: { code: string; discount: string } | null;
  items: CartLine[];
  cart_token?: string;
}

/** Auth wins; otherwise the guest cart_token identifies the cart. */
function cartAuth(token?: string | null) {
  return token ? { headers: { Authorization: `Bearer ${token}` } } : null;
}

export async function getCart(
  token?: string | null,
  cartToken?: string | null,
): Promise<CartData> {
  const auth = cartAuth(token);
  return apiGet<CartData>(
    "/cart",
    auth ? undefined : { cart_token: cartToken ?? undefined },
    undefined,
    auth?.headers,
  );
}

function cartBody(
  payload: Record<string, unknown>,
  token?: string | null,
  cartToken?: string | null,
) {
  return token ? payload : { ...payload, cart_token: cartToken ?? undefined };
}

export async function addToCart(
  productId: number,
  quantity: number,
  token?: string | null,
  cartToken?: string | null,
): Promise<CartData> {
  return apiPost<CartData>(
    "/cart/add",
    cartBody({ product_id: productId, quantity }, token, cartToken),
    token ?? undefined,
  );
}

export async function updateCartItem(
  itemId: number,
  quantity: number,
  token?: string | null,
  cartToken?: string | null,
): Promise<CartData> {
  return apiPost<CartData>(
    "/cart/update",
    cartBody({ item_id: itemId, quantity }, token, cartToken),
    token ?? undefined,
  );
}

export async function removeCartItem(
  itemId: number,
  token?: string | null,
  cartToken?: string | null,
): Promise<CartData> {
  return apiPost<CartData>(
    "/cart/remove",
    cartBody({ item_id: itemId }, token, cartToken),
    token ?? undefined,
  );
}

export async function mergeCart(
  token: string,
  cartToken: string,
): Promise<CartData> {
  return apiPost<CartData>("/cart/merge", { cart_token: cartToken }, token);
}

// ---------------------------------------------------------------------------
// Checkout
// ---------------------------------------------------------------------------

export interface CheckoutInput {
  name?: string;
  phone?: string;
  email?: string;
  address_id?: number;
  division?: string;
  district?: string;
  area?: string;
  address?: string;
  landmark?: string;
  postcode?: string;
  payment_method?: string;
  note?: string;
}

export interface PlacedOrder {
  order_number: string;
  status: string;
  payment: { method: string | null; status: string | null };
  currency: string;
  totals: {
    subtotal: string;
    shipping_charge: string;
    discount: string;
    tax: string;
    total: string;
  };
  item_count: number;
  placed_at: string;
}

export async function checkout(
  input: CheckoutInput,
  token?: string | null,
  cartToken?: string | null,
): Promise<PlacedOrder> {
  return apiPost<PlacedOrder>(
    "/checkout",
    cartBody(input as Record<string, unknown>, token, cartToken),
    token ?? undefined,
  );
}

// ---------------------------------------------------------------------------
// Wishlist toggle (customer bearer)
// ---------------------------------------------------------------------------

export async function toggleWishlist(
  token: string,
  productId: number,
): Promise<{ in_wishlist: boolean; count: number }> {
  return apiPost<{ action: string; in_wishlist: boolean; count: number }>(
    "/wishlist/toggle",
    { product_id: productId },
    token,
  );
}

// ---------------------------------------------------------------------------
// Submit a product review (customer bearer)
// ---------------------------------------------------------------------------

export async function submitReview(
  token: string,
  slug: string,
  input: { rating: number; title?: string; comment?: string },
): Promise<void> {
  await apiPost(
    `/products/${encodeURIComponent(slug)}/reviews`,
    input,
    token,
  );
}
