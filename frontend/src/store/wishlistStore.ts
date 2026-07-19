import { create } from "zustand";
import { fetchWishlist, toggleWishlist } from "@/lib/api";
import { useAuthStore } from "@/store/authStore";

interface WishlistStore {
  ids: number[];
  loaded: boolean;
  load: () => Promise<void>;
  /** Returns "noauth" when not logged in, else the new wished state. */
  toggle: (productId: number) => Promise<boolean | "noauth">;
  isWished: (productId: number) => boolean;
  reset: () => void;
}

const token = () => useAuthStore.getState().token;

export const useWishlistStore = create<WishlistStore>((set, get) => ({
  ids: [],
  loaded: false,

  load: async () => {
    const t = token();
    if (!t) {
      set({ ids: [], loaded: true });
      return;
    }
    try {
      const items = await fetchWishlist(t);
      set({ ids: items.map((i) => i.product_id), loaded: true });
    } catch (e) {
      console.error(e);
    }
  },

  toggle: async (productId) => {
    const t = token();
    if (!t) return "noauth";
    try {
      const res = await toggleWishlist(t, productId);
      set((s) => ({
        ids: res.in_wishlist
          ? [...new Set([...s.ids, productId])]
          : s.ids.filter((id) => id !== productId),
      }));
      return res.in_wishlist;
    } catch (e) {
      console.error(e);
      return get().isWished(productId);
    }
  },

  isWished: (productId) => get().ids.includes(productId),

  reset: () => set({ ids: [], loaded: false }),
}));
