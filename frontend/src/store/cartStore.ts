import { create } from "zustand";
import { persist } from "zustand/middleware";
import {
  getCart,
  addToCart,
  updateCartItem,
  removeCartItem,
  mergeCart,
  type CartData,
  type CartLine,
} from "@/lib/api";
import { type Product } from "@/types";
import { useAuthStore } from "@/store/authStore";

interface CartStore {
  cartToken: string | null; // guest cart identifier (persisted)
  items: CartLine[];
  itemCount: number;
  subtotal: string;
  discount: string;
  total: string;
  currency: string;
  isOpen: boolean;
  loading: boolean;
  error: string | null;

  openCart: () => void;
  closeCart: () => void;
  refresh: () => Promise<void>;
  addItem: (product: Product, quantity?: number) => Promise<void>;
  updateQuantity: (lineId: number, quantity: number) => Promise<void>;
  removeItem: (lineId: number) => Promise<void>;
  /** Fold a guest cart into the user cart after login, then reload. */
  mergeGuestCart: () => Promise<void>;
  /** Drop the in-memory + guest cart (called on logout). */
  reset: () => void;
}

const token = () => useAuthStore.getState().token;

export const useCartStore = create<CartStore>()(
  persist(
    (set, get) => {
      const apply = (data: CartData) =>
        set({
          items: data.items,
          itemCount: data.item_count,
          subtotal: data.subtotal,
          discount: data.discount,
          total: data.total,
          currency: data.currency,
          // The server mints a guest token on first write — keep it for guests.
          cartToken: token() ? null : (data.cart_token ?? get().cartToken),
        });

      return {
        cartToken: null,
        items: [],
        itemCount: 0,
        subtotal: "0.00",
        discount: "0.00",
        total: "0.00",
        currency: "BDT",
        isOpen: false,
        loading: false,
        error: null,

        openCart: () => set({ isOpen: true }),
        closeCart: () => set({ isOpen: false }),

        refresh: async () => {
          try {
            apply(await getCart(token(), get().cartToken));
          } catch (e) {
            console.error(e);
          }
        },

        addItem: async (product, quantity = 1) => {
          set({ loading: true, error: null, isOpen: true });
          try {
            const data = await addToCart(
              Number(product.id),
              quantity,
              token(),
              get().cartToken,
            );
            apply(data);
          } catch (e) {
            set({ error: e instanceof Error ? e.message : "Could not add item" });
          } finally {
            set({ loading: false });
          }
        },

        updateQuantity: async (lineId, quantity) => {
          try {
            apply(
              await updateCartItem(lineId, quantity, token(), get().cartToken),
            );
          } catch (e) {
            console.error(e);
          }
        },

        removeItem: async (lineId) => {
          try {
            apply(await removeCartItem(lineId, token(), get().cartToken));
          } catch (e) {
            console.error(e);
          }
        },

        mergeGuestCart: async () => {
          const t = token();
          const guest = get().cartToken;
          try {
            if (t && guest) {
              apply(await mergeCart(t, guest));
              set({ cartToken: null });
            } else {
              await get().refresh();
            }
          } catch (e) {
            console.error(e);
          }
        },

        reset: () =>
          set({
            cartToken: null,
            items: [],
            itemCount: 0,
            subtotal: "0.00",
            discount: "0.00",
            total: "0.00",
            isOpen: false,
          }),
      };
    },
    {
      name: "babusona-cart",
      // Only the guest token is persisted; items are always reloaded from the
      // server so the cart survives refresh without going stale.
      partialize: (s) => ({ cartToken: s.cartToken }),
    },
  ),
);
