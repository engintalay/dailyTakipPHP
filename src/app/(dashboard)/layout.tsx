import { Sidebar } from "@/components/sidebar";
import { ImpersonationBanner } from "@/components/impersonation-banner";
import { ThemeToggle } from "@/components/theme-toggle";
import { auth } from "@/lib/auth";
import { redirect } from "next/navigation";

export default async function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const session = await auth();
  if (!session?.user) redirect("/login");

  return (
    <div className="flex min-h-screen">
      <Sidebar />
      <div className="flex-1 ml-64">
        <ImpersonationBanner />
        <header className="h-14 border-b border-border flex items-center justify-end px-6 gap-3 bg-background">
          <ThemeToggle />
        </header>
        <main className="p-6">{children}</main>
      </div>
    </div>
  );
}
