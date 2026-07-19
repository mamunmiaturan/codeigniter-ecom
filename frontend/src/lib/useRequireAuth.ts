"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { useAuthStore } from "@/store/authStore";

/**
 * Guards a client page behind a customer session. Returns the token and a
 * `ready` flag that's only true once mounted AND authenticated — use it to
 * hold rendering until the persisted token has rehydrated (avoids SSR/CSR
 * hydration mismatches and redirects unauthenticated visitors to login).
 */
export function useRequireAuth() {
  const router = useRouter();
  const token = useAuthStore((s) => s.token);
  const [mounted, setMounted] = useState(false);

  useEffect(() => setMounted(true), []);

  useEffect(() => {
    if (mounted && !token) router.replace("/account/login");
  }, [mounted, token, router]);

  return { token, ready: mounted && Boolean(token) };
}
