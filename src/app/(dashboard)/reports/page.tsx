"use client";

import { useState, useEffect } from "react";
import { formatDateShort, formatDateOnly, getWeekRange, getMonthRange, STATUS_TURKCE } from "@/lib/utils";

export default function ReportsPage() {
  const [period, setPeriod] = useState<"weekly" | "monthly">("weekly");
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadData();
  }, [period]);

  async function loadData() {
    setLoading(true);
    const range = period === "weekly" ? getWeekRange() : getMonthRange();
    const startStr = formatDateOnly(range.start);
    const endStr = formatDateOnly(range.end);

    const [notesRes, statusRes, attendanceRes, usersRes] = await Promise.all([
      fetch(`/api/daily-notes?startDate=${startStr}&endDate=${endStr}`),
      fetch(`/api/status?startDate=${startStr}&endDate=${endStr}`),
      fetch(`/api/attendance?startDate=${startStr}&endDate=${endStr}`),
      fetch("/api/users"),
    ]);

    const notes = await notesRes.json();
    const statuses = await statusRes.json();
    const attendance = await attendanceRes.json();
    const users = await usersRes.json();

    const userNoteCount: Record<string, number> = {};
    const userStatusCount: Record<string, Record<string, number>> = {};
    const userAttendance: Record<string, { present: number; total: number }> = {};

    users.forEach((u: any) => {
      userNoteCount[u.id] = 0;
      userStatusCount[u.id] = { OFFICE: 0, REMOTE: 0, LEAVE: 0, SICK: 0 };
      userAttendance[u.id] = { present: 0, total: 0 };
    });

    notes.forEach((n: any) => {
      if (userNoteCount[n.userId] !== undefined) userNoteCount[n.userId]++;
    });

    statuses.forEach((s: any) => {
      if (userStatusCount[s.userId] && userStatusCount[s.userId][s.type] !== undefined) {
        userStatusCount[s.userId][s.type]++;
      }
    });

    attendance.forEach((a: any) => {
      if (userAttendance[a.userId]) {
        userAttendance[a.userId].total++;
        if (a.present) userAttendance[a.userId].present++;
      }
    });

    const tagCount: Record<string, number> = {};
    notes.forEach((n: any) => {
      (n.tags || "").split(",").filter(Boolean).forEach((tag: string) => {
        const t = tag.trim();
        tagCount[t] = (tagCount[t] || 0) + 1;
      });
    });

    const dateLabels: string[] = [];
    const dateNoteCount: number[] = [];
    const dateCountMap: Record<string, number> = {};
    notes.forEach((n: any) => {
      const key = formatDateShort(n.date);
      dateCountMap[key] = (dateCountMap[key] || 0) + 1;
    });
    Object.entries(dateCountMap).forEach(([date, count]) => {
      dateLabels.push(date);
      dateNoteCount.push(count);
    });

    setData({
      users,
      notes,
      statuses,
      attendance,
      userNoteCount,
      userStatusCount,
      userAttendance,
      tagCount,
      dateLabels,
      dateNoteCount,
      range: { start: startStr, end: endStr },
    });
    setLoading(false);
  }

  if (loading) return <div className="text-muted-foreground">Yükleniyor...</div>;
  if (!data) return null;

  const totalNotes = data.notes.length;
  const totalStatuses = data.statuses.length;
  const totalAttendance = data.attendance.length;

  const periodLabel = period === "weekly" ? "Haftalık" : "Aylık";
  const topTags = Object.entries(data.tagCount as Record<string, number>)
    .sort(([, a], [, b]) => b - a)
    .slice(0, 10);

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Raporlar</h1>
        <div className="flex gap-2">
          <button
            onClick={() => setPeriod("weekly")}
            className={`px-4 py-2 rounded-lg text-sm ${
              period === "weekly" ? "bg-blue-600 text-white" : "bg-secondary hover:bg-secondary/80"
            }`}
          >
            Haftalık
          </button>
          <button
            onClick={() => setPeriod("monthly")}
            className={`px-4 py-2 rounded-lg text-sm ${
              period === "monthly" ? "bg-blue-600 text-white" : "bg-secondary hover:bg-secondary/80"
            }`}
          >
            Aylık
          </button>
        </div>
      </div>

      <div className="bg-card border border-border rounded-xl p-5" id="report-content">
        <h2 className="text-lg font-semibold mb-1">{periodLabel} Özet Rapor</h2>
        <p className="text-sm text-muted-foreground mb-4">
          {data.range.start} — {data.range.end}
        </p>

        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
          <div className="bg-blue-50 dark:bg-blue-950/20 rounded-xl p-4 text-center">
            <div className="text-3xl font-bold text-blue-600">{totalNotes}</div>
            <div className="text-sm text-muted-foreground">Daily Not</div>
          </div>
          <div className="bg-emerald-50 dark:bg-emerald-950/20 rounded-xl p-4 text-center">
            <div className="text-3xl font-bold text-emerald-600">{totalStatuses}</div>
            <div className="text-sm text-muted-foreground">Durum Girişi</div>
          </div>
          <div className="bg-amber-50 dark:bg-amber-950/20 rounded-xl p-4 text-center">
            <div className="text-3xl font-bold text-amber-600">{totalAttendance}</div>
            <div className="text-sm text-muted-foreground">Katılım Kaydı</div>
          </div>
        </div>

        <h3 className="font-semibold mb-3">Kullanıcı Bazında Özet</h3>
        <div className="overflow-x-auto mb-6">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-muted/50">
                <th className="text-left p-2 font-medium">İsim</th>
                <th className="text-center p-2 font-medium">Not</th>
                <th className="text-center p-2 font-medium">🏢 Ofis</th>
                <th className="text-center p-2 font-medium">🏠 Remote</th>
                <th className="text-center p-2 font-medium">🌴 İzin</th>
                <th className="text-center p-2 font-medium">🤒 Hasta</th>
                <th className="text-center p-2 font-medium">Katılım</th>
              </tr>
            </thead>
            <tbody>
              {data.users.map((u: any) => {
                const notes = data.userNoteCount[u.id] || 0;
                const statuses = data.userStatusCount[u.id] || {};
                const att = data.userAttendance[u.id] || { present: 0, total: 0 };
                const attRate = att.total > 0 ? Math.round((att.present / att.total) * 100) : 0;

                return (
                  <tr key={u.id} className="border-t border-border">
                    <td className="p-2 font-medium">{u.name}</td>
                    <td className="text-center p-2">{notes}</td>
                    <td className="text-center p-2">{statuses.OFFICE || 0}</td>
                    <td className="text-center p-2">{statuses.REMOTE || 0}</td>
                    <td className="text-center p-2">{statuses.LEAVE || 0}</td>
                    <td className="text-center p-2">{statuses.SICK || 0}</td>
                    <td className="text-center p-2">{attRate}%</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>

        {topTags.length > 0 && (
          <>
            <h3 className="font-semibold mb-3">En Çok Kullanılan Etiketler</h3>
            <div className="flex flex-wrap gap-2 mb-6">
              {topTags.map(([tag, count]: [string, number]) => (
                <span
                  key={tag}
                  className="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
                >
                  {tag} ({count})
                </span>
              ))}
            </div>
          </>
        )}
      </div>

      <div className="flex gap-2">
        <button
          onClick={() => window.print()}
          className="px-4 py-2 bg-slate-800 text-white rounded-lg text-sm hover:bg-slate-700"
        >
          🖨️ Yazdır / PDF
        </button>
      </div>
    </div>
  );
}
