export interface ProductColor {
  name: string;
  hex: string;
  inStock: boolean;
}

export interface ProductSize {
  label: string;
  inStock: boolean;
}

export interface ProductReview {
  id: string;
  name: string;
  avatar: string;
  rating: number;
  comment: string;
  commentBn?: string;
  date: string;
  verified: boolean;
}

export interface Product {
  id: string;
  slug: string;
  name: string;
  nameBn: string;
  description: string;
  descriptionBn: string;
  price: number;
  originalPrice?: number;
  discount?: number;
  images: string[];
  category: string;
  shopCategory: string[];
  colors: ProductColor[];
  sizes: ProductSize[];
  isBespoke: boolean;
  isNew: boolean;
  isBestseller: boolean;
  rating: number;
  reviewCount: number;
  inStock: boolean;
  reviews: ProductReview[];
  tags: string[];
  attributes?: { code: string; name: string; value: string }[];
}

export interface CartItem {
  id: string;
  product: Product;
  quantity: number;
  selectedColor: ProductColor;
  selectedSize?: ProductSize;
  bespokeText?: string;
  totalPrice: number;
}

export interface Category {
  id: string;
  label: string;
  labelBn: string;
  image: string;
  href: string;
  emoji: string;
}
