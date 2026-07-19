import { create } from "zustand";
import { persist } from "zustand/middleware";
import {
  login as apiLogin,
  register as apiRegister,
  getProfile as apiGetProfile,
  type AuthTokens,
  type CustomerProfile,
  type RegisterInput,
} from "@/lib/api";
import { useCartStore } from "@/store/cartStore";
import { useWishlistStore } from "@/store/wishlistStore";

interface AuthState {
  token: string | null;
  refreshToken: string | null;
  customer: CustomerProfile | null;
  loading: boolean;
  error: string | null;

  login: (email: string, password: string) => Promise<boolean>;
  register: (input: RegisterInput) => Promise<boolean>;
  refreshProfile: () => Promise<void>;
  logout: () => void;
  clearError: () => void;
}

function storeTokens(tokens: AuthTokens) {
  return { token: tokens.access_token, refreshToken: tokens.refresh_token };
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      token: null,
      refreshToken: null,
      customer: null,
      loading: false,
      error: null,

      login: async (email, password) => {
        set({ loading: true, error: null });
        try {
          const { tokens, mfaRequired } = await apiLogin(email, password);
          if (mfaRequired || !tokens) {
            set({
              loading: false,
              error:
                "This account uses two-factor authentication, which isn't supported here yet.",
            });
            return false;
          }
          const profile = await apiGetProfile(tokens.access_token);
          set({ ...storeTokens(tokens), customer: profile, loading: false });
          void useCartStore.getState().mergeGuestCart();
          void useWishlistStore.getState().load();
          return true;
        } catch (err) {
          set({
            loading: false,
            error: err instanceof Error ? err.message : "Login failed",
          });
          return false;
        }
      },

      register: async (input) => {
        set({ loading: true, error: null });
        try {
          const { customer, auth } = await apiRegister(input);
          set({ ...storeTokens(auth), customer, loading: false });
          void useCartStore.getState().mergeGuestCart();
          void useWishlistStore.getState().load();
          return true;
        } catch (err) {
          set({
            loading: false,
            error: err instanceof Error ? err.message : "Registration failed",
          });
          return false;
        }
      },

      refreshProfile: async () => {
        const token = get().token;
        if (!token) return;
        try {
          const profile = await apiGetProfile(token);
          set({ customer: profile });
        } catch {
          // Token expired or revoked → drop the session.
          set({ token: null, refreshToken: null, customer: null });
        }
      },

      logout: () => {
        set({ token: null, refreshToken: null, customer: null, error: null });
        useCartStore.getState().reset();
        useWishlistStore.getState().reset();
      },

      clearError: () => set({ error: null }),
    }),
    {
      name: "babusona-auth",
      partialize: (s) => ({
        token: s.token,
        refreshToken: s.refreshToken,
        customer: s.customer,
      }),
    },
  ),
);
