"use client";

import { useState, useEffect } from "react";
import { useSession } from "next-auth/react";
import { formatDateOnly, getWeekRange } from "@/lib/utils";
import type { SessionUser } from "@/lib/types";

export default function AttendancePage() {
  const { data: session } = useSession();
  const user = session?.user as SessionUser | undefined;

  const [users, setUsers] = useState<any[]>([]);
  const [records, setRecords] = useState<any[]>([]);
  const [weekStart, setWeekStart] = useState(() => {
    const { start } = getWeekRange();
    return formatDateOnly(start);
  });

  useEffect(() => {
    loadData();
  }, [weekStart]);

  async function loadData() {
    const u = await fetch("/api/users").then((r) => r.json());
    setUsers(u);

    const start = new Date(weekStart);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);
    const endStr = formatDateOnly(end);

    const r = await fetch(`/api/attendance?startDate=${weekStart}&endDate=${endStr}`).then((r) => r.json());
    setRecords(r);
  }

  async function toggleAttendance(date: string, currentPresent: boolean) {
    await fetch("/api/attendance", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ date, present: !currentPresent }),
    });
    loadData();
  }

  const days = Array.from({ length: 7 }, (_, i) => {
    const d = new Date(weekStart);
    d.setDate(d.getDate() + i);
    return d;
  });

  const recordMap = new Map<string, boolean>();
  records.forEach((r: any) => {
    const key = `${r.userId}-${formatDateOnly(r.date)}`;
    recordMap.set(key, r.present);
  });

  function getAttendanceCount(userId: string) {
    let present = 0;
    let total = 0;
    days.forEach((d) => {
      const key = `${userId}-${formatDateOnly(d)}`;
      if (recordMap.has(key)) {
        total++;
        if (recordMap.get(key)) present++;
      }
    });
    return { present, total };
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Katılım Takibi</h1>
        <div className="flex gap-2">
          <button
            onClick={() => {
              const d = new Date(weekStart);
              d.setDate(d.getDate() - 7);
              setWeekStart(formatDateOnly(d));
            }}
            className="px-3 py-1 text-sm border border-border rounded-lg hover:bg-secondary"
          >
            ← Geçen Hafta
          </button>
          <button
            onClick={() => {
              const d = new Date(weekStart);
              d.setDate(d.getDate() + 7);
              setWeekStart(formatDateOnly(d));
            }}
            className="px-3 py-1 text-sm border border-border rounded-lg hover:bg-secondary"
          >
            Sonraki Hafta →
          </button>
        </div>
      </div>

      <div className="bg-card border border-border rounded-xl overflow-x-auto">
        <table className="w-full text-sm">
          <thead>
            <tr className="bg-muted/50">
              <th className="text-left p-3 font-medium">İsim</th>
              {days.map((d, i) => (
                <th key={i} className={`text-center p-3 font-medium ${formatDateOnly(d) === formatDateOnly(new Date()) ? "text-blue-600" : ""}`}>
                  <div>{d.toLocaleDateString("tr-TR", { weekday: "short" })}</div>
                  <div className="text-xs text-muted-foreground">{d.getDate()}</div>
                </th>
              ))}
              <th className="text-center p-3 font-medium">Oran</th>
              <th className="text-center p-3 font-medium">Hızlı İşlem</th>
            </tr>
          </thead>
          <tbody>
            {users.map((u) => {
              const { present, total } = getAttendanceCount(u.id);
              const rate = total > 0 ? Math.round((present / total) * 100) : 0;

              return (
                <tr key={u.id} className="border-t border-border hover:bg-muted/20">
                  <td className="p-3 font-medium">
                    <div className="flex items-center gap-2">
                      <div className="w-7 h-7 rounded-full bg-blue-600 flex items-center justify-center text-white text-xs font-bold">
                        {u.name.charAt(0)}
                      </div>
                      {u.name}
                    </div>
                  </td>
                  {days.map((d) => {
                    const key = `${u.id}-${formatDateOnly(d)}`;
                    const isPresent = recordMap.get(key);
                    const isFuture = d > new Date();

                    return (
                      <td key={d.toISOString()} className="text-center p-3">
                        {isFuture ? (
                          <span className="text-muted-foreground text-xs">—</span>
                        ) : isPresent === undefined ? (
                          <span className="text-muted-foreground text-xs">?</span>
                        ) : isPresent ? (
                          <span className="text-lg">✅</span>
                        ) : (
                          <span className="text-lg">❌</span>
                        )}
                      </td>
                    );
                  })}
                  <td className="text-center p-3">
                    <span className={`text-xs px-2 py-0.5 rounded-full font-medium ${
                      rate >= 80 ? "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400" :
                      rate >= 50 ? "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400" :
                      "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                    }`}>
                      {rate}%
                    </span>
                  </td>
                  <td className="text-center p-3">
                    {user?.id === u.id && days.map((d) => {
                      const key = `${u.id}-${formatDateOnly(d)}`;
                      const isPresent = recordMap.get(key);
                      if (d > new Date()) return null;
                      return (
                        <button
                          key={d.toISOString()}
                          onClick={() => toggleAttendance(formatDateOnly(d), isPresent ?? true)}
                          className="text-xs px-2 py-1 border border-border rounded hover:bg-secondary mr-1"
                        >
                          {formatDateOnly(d) === formatDateOnly(new Date()) ? "Bugün" : d.getDate() + ""}
                        </button>
                      );
                    }).filter(Boolean).slice(0, 1)}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>

      <div className="bg-card border border-border rounded-xl p-4">
        <h2 className="font-semibold mb-2">Hızlı İşlem</h2>
        <p className="text-sm text-muted-foreground mb-3">
          Bugünkü katılım durumunu işaretle:
        </p>
        <div className="flex gap-2">
          <button
            onClick={() => toggleAttendance(formatDateOnly(new Date()), true)}
            className="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm hover:bg-emerald-700"
          >
            ✅ Katıldı
          </button>
          <button
            onClick={() => toggleAttendance(formatDateOnly(new Date()), false)}
            className="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700"
          >
            ❌ Katılmadı
          </button>
        </div>
      </div>
    </div>
  );
}
