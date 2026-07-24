"use client";

import { useSession } from "next-auth/react";
import { signOut } from "next-auth/react";
import type { SessionUser } from "@/lib/types";

export function ImpersonationBanner() {
  const { data: session } = useSession();
  const user = session?.user as SessionUser | undefined;

  if (!user?.impersonatingUserId) return null;

  return (
    <div className="bg-amber-500 text-amber-950 px-4 py-2 text-sm flex items-center justify-between">
      <span>
        🔐 <strong>Admin modu:</strong> Bu oturum bir kullanıcı hesabı üzerinden
        yürütülüyor.
      </span>
      <button
        onClick={() =>
          signOut({ callbackUrl: "/login" })
        }
        className="underline font-medium hover:text-amber-900"
      >
        Oturumu Kapat
      </button>
    </div>
  );
}
