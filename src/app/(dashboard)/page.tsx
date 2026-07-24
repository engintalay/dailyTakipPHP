import { prisma } from "@/lib/prisma";
import { auth } from "@/lib/auth";
import { redirect } from "next/navigation";
import { formatDateShort, STATUS_TURKCE, STATUS_COLORS } from "@/lib/utils";
import Link from "next/link";
import { DailyCheckBanner } from "@/components/daily-check";

export const dynamic = "force-dynamic";

export default async function DashboardPage() {
  const session = await auth();
  if (!session?.user) redirect("/login");

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const tomorrow = new Date(today);
  tomorrow.setDate(tomorrow.getDate() + 1);

  const users = await prisma.user.findMany({
    where: { isActive: true },
    orderBy: { name: "asc" },
  });

  const todayStatuses = await prisma.dailyStatus.findMany({
    where: {
      date: { gte: today, lt: tomorrow },
    },
    include: { user: true },
  });

  const recentNotes = await prisma.dailyNote.findMany({
    take: 10,
    orderBy: { createdAt: "desc" },
    include: { user: true },
  });

  const statusMap = new Map(todayStatuses.map((s) => [s.userId, s]));

  const todayNotes = await prisma.dailyNote.findMany({
    where: { date: { gte: today, lt: tomorrow } },
    select: { userId: true },
  });
  const userIdsWithNote = new Set(todayNotes.map((n) => n.userId));
  const missingUsers = users.filter((u) => !userIdsWithNote.has(u.id));

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Dashboard</h1>
        <div className="flex gap-2">
          <Link
            href="/daily"
            className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 transition-colors"
          >
            + Not Ekle
          </Link>
          <Link
            href="/status"
            className="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-lg text-sm hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
          >
            Durum Belirt
          </Link>
        </div>
      </div>

      <DailyCheckBanner />

      {missingUsers.length > 0 && (
        <div className="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
          <div className="flex items-center gap-2 mb-2">
            <span>⏳</span>
            <h2 className="font-semibold text-amber-800 dark:text-amber-300">
              Bugün Not Girmeyenler
            </h2>
          </div>
          <div className="flex flex-wrap gap-2">
            {missingUsers.map((u) => (
              <span
                key={u.id}
                className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 text-sm"
              >
                <div className="w-5 h-5 rounded-full bg-amber-500 flex items-center justify-center text-white text-[10px] font-bold">
                  {u.name.charAt(0)}
                </div>
                {u.name}
              </span>
            ))}
          </div>
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-card border border-border rounded-xl p-5">
          <h2 className="font-semibold text-lg mb-4">Bugün Kim Nerede?</h2>
          <div className="space-y-2">
            {users.length === 0 && (
              <p className="text-sm text-muted-foreground">Henüz kullanıcı yok.</p>
            )}
            {users.map((user) => {
              const status = statusMap.get(user.id);
              return (
                <div
                  key={user.id}
                  className="flex items-center justify-between px-3 py-2 rounded-lg bg-secondary/50"
                >
                  <div className="flex items-center gap-3">
                    <div className="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold">
                      {user.name.charAt(0)}
                    </div>
                    <span className="text-sm font-medium">{user.name}</span>
                  </div>
                  {status ? (
                    <span
                      className={`text-xs px-2.5 py-1 rounded-full font-medium ${
                        STATUS_COLORS[status.type]
                      }`}
                    >
                      {STATUS_TURKCE[status.type] || status.type}
                    </span>
                  ) : (
                    <span className="text-xs text-muted-foreground">Belirtilmemiş</span>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        <div className="bg-card border border-border rounded-xl p-5">
          <div className="flex items-center justify-between mb-4">
            <h2 className="font-semibold text-lg">Son Daily Notlar</h2>
            <Link
              href="/daily"
              className="text-xs text-blue-600 hover:underline"
            >
              Tümünü Gör
            </Link>
          </div>
          <div className="space-y-3">
            {recentNotes.length === 0 && (
              <p className="text-sm text-muted-foreground">
                Henüz daily notu eklenmemiş.
              </p>
            )}
            {recentNotes.map((note) => (
              <div
                key={note.id}
                className="p-3 rounded-lg bg-secondary/30 border border-border/50"
              >
                <div className="flex items-center gap-2 mb-1">
                  <span className="text-xs font-medium text-muted-foreground">
                    {note.user.name}
                  </span>
                  <span className="text-xs text-muted-foreground">·</span>
                  <span className="text-xs text-muted-foreground">
                    {formatDateShort(note.date)}
                  </span>
                </div>
                <p className="text-sm">{note.content}</p>
                {note.tags && (
                  <div className="flex gap-1 mt-1.5">
                    {note.tags.split(",").filter(Boolean).map((tag) => (
                      <span
                        key={tag}
                        className="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
                      >
                        {tag.trim()}
                      </span>
                    ))}
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}
