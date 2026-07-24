"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { cn } from "@/lib/utils";
import { useSession, signOut } from "next-auth/react";
import type { SessionUser } from "@/lib/types";

const NAV_ITEMS = [
  { href: "/", label: "Dashboard", icon: "📊" },
  { href: "/daily", label: "Daily Notlar", icon: "📝" },
  { href: "/status", label: "Durum Takibi", icon: "📍" },
  { href: "/attendance", label: "Katılım", icon: "✅" },
  { href: "/search", label: "Arama", icon: "🔍" },
  { href: "/reports", label: "Raporlar", icon: "📈" },
];

const ADMIN_ITEMS = [
  { href: "/admin/users", label: "Kullanıcılar", icon: "👥" },
];

export function Sidebar() {
  const pathname = usePathname();
  const router = useRouter();
  const { data: session } = useSession();
  const user = session?.user as SessionUser | undefined;

  return (
    <aside className="w-64 bg-sidebar text-sidebar-foreground flex flex-col h-screen fixed left-0 top-0 z-30">
      <div className="p-5 border-b border-sidebar-muted">
        <Link href="/" className="text-lg font-bold tracking-tight">
          dailyTakip
        </Link>
        <p className="text-xs text-sidebar-foreground/60 mt-0.5">
          Ekip Takip Sistemi
        </p>
      </div>

      <nav className="flex-1 p-3 space-y-1 overflow-y-auto">
        {NAV_ITEMS.map((item) => {
          const isActive = pathname === item.href || pathname.startsWith(item.href + "/");
          return (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                "flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors",
                isActive
                  ? "bg-blue-600 text-white"
                  : "text-sidebar-foreground/80 hover:bg-sidebar-muted hover:text-sidebar-foreground"
              )}
            >
              <span className="text-base">{item.icon}</span>
              {item.label}
            </Link>
          );
        })}

        {user?.role === "ADMIN" && (
          <>
            <div className="pt-3 pb-1">
              <p className="px-3 text-xs font-semibold uppercase text-sidebar-foreground/40">
                Yönetim
              </p>
            </div>
            {ADMIN_ITEMS.map((item) => {
              const isActive = pathname === item.href;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={cn(
                    "flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors",
                    isActive
                      ? "bg-blue-600 text-white"
                      : "text-sidebar-foreground/80 hover:bg-sidebar-muted hover:text-sidebar-foreground"
                  )}
                >
                  <span className="text-base">{item.icon}</span>
                  {item.label}
                </Link>
              );
            })}
          </>
        )}
      </nav>

      <div className="p-3 border-t border-sidebar-muted space-y-1">
        <Link
          href="/"
          className="flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-sidebar-foreground/80 hover:bg-sidebar-muted transition-colors"
        >
          <div className="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold">
            {user?.name?.charAt(0) || "?"}
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-sm font-medium truncate">{user?.name}</p>
            <p className="text-xs text-sidebar-foreground/50 truncate">
              {user?.role === "ADMIN" ? "Admin" : "Üye"}
            </p>
          </div>
        </Link>
        <button
          onClick={() => signOut({ callbackUrl: "/login" })}
          className="flex items-center gap-3 w-full px-3 py-2 rounded-lg text-sm text-red-400 hover:bg-red-500/10 transition-colors"
        >
          <span className="text-base">🚪</span>
          Çıkış Yap
        </button>
      </div>
    </aside>
  );
}
