"use client";

import { useState, useEffect } from "react";
import { useSession } from "next-auth/react";
import { formatDateOnly, STATUS_TURKCE, STATUS_COLORS } from "@/lib/utils";
import type { SessionUser } from "@/lib/types";

const STATUS_OPTIONS = [
  { type: "OFFICE", label: "🏢 Ofiste", color: "border-emerald-500 bg-emerald-50 dark:bg-emerald-950/20" },
  { type: "REMOTE", label: "🏠 Remote", color: "border-blue-500 bg-blue-50 dark:bg-blue-950/20" },
  { type: "LEAVE", label: "🌴 İzinli", color: "border-amber-500 bg-amber-50 dark:bg-amber-950/20" },
  { type: "SICK", label: "🤒 Hasta", color: "border-red-500 bg-red-50 dark:bg-red-950/20" },
];

export default function StatusPage() {
  const { data: session } = useSession();
  const user = session?.user as SessionUser | undefined;

  const [todayStatus, setTodayStatus] = useState<string | null>(null);
  const [note, setNote] = useState("");
  const [loading, setLoading] = useState(true);
  const [monthStatuses, setMonthStatuses] = useState<any[]>([]);
  const [currentMonth, setCurrentMonth] = useState(() => {
    const now = new Date();
    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}`;
  });

  useEffect(() => {
    loadTodayStatus();
    loadMonthStatuses();
  }, [currentMonth]);

  async function loadTodayStatus() {
    const today = formatDateOnly(new Date());
    const res = await fetch(`/api/status?date=${today}&userId=${user?.id}`);
    const data = await res.json();
    if (data.length > 0) {
      setTodayStatus(data[0].type);
      setNote(data[0].note || "");
    } else {
      setTodayStatus(null);
      setNote("");
    }
    setLoading(false);
  }

  async function loadMonthStatuses() {
    const [year, month] = currentMonth.split("-").map(Number);
    const startDate = new Date(year, month - 1, 1).toISOString().split("T")[0];
    const endDate = new Date(year, month, 0).toISOString().split("T")[0];
    const res = await fetch(`/api/status?startDate=${startDate}&endDate=${endDate}`);
    const data = await res.json();
    setMonthStatuses(data);
  }

  async function setStatus(type: string) {
    const today = formatDateOnly(new Date());
    await fetch("/api/status", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ date: today, type, note }),
    });
    setTodayStatus(type);
    loadMonthStatuses();
  }

  function getDaysInMonth(year: number, month: number) {
    return new Date(year, month, 0).getDate();
  }

  function getFirstDayOfMonth(year: number, month: number) {
    return new Date(year, month - 1, 1).getDay();
  }

  const today = formatDateOnly(new Date());
  const [year, month] = currentMonth.split("-").map(Number);
  const daysInMonth = getDaysInMonth(year, month);
  const firstDay = getFirstDayOfMonth(year, month);

  const monthStatusMap = new Map<string, string>();
  monthStatuses.forEach((s: any) => {
    const dateKey = formatDateOnly(s.date);
    monthStatusMap.set(`${s.userId}-${dateKey}`, s.type);
  });

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold">Durum Takibi</h1>

      <div className="bg-card border border-border rounded-xl p-6">
        <h2 className="font-semibold mb-4">Bugünkü Durumun</h2>
        <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
          {STATUS_OPTIONS.map((opt) => (
            <button
              key={opt.type}
              onClick={() => setStatus(opt.type)}
              className={`p-4 rounded-xl border-2 text-center transition-all ${
                todayStatus === opt.type
                  ? `${opt.color} border-2 scale-105 shadow-md`
                  : "border-border hover:border-slate-300 dark:hover:border-slate-600"
              }`}
            >
              <div className="text-lg">{opt.label}</div>
            </button>
          ))}
        </div>
        <div className="mt-4">
          <label className="block text-sm font-medium mb-1">Not (opsiyonel)</label>
          <input
            value={note}
            onChange={(e) => setNote(e.target.value)}
            onBlur={() => todayStatus && setStatus(todayStatus)}
            className="w-full px-3 py-2 border border-border rounded-lg bg-background text-sm"
            placeholder="Eklemek istediğin bir not var mı?"
          />
        </div>
      </div>

      <div className="bg-card border border-border rounded-xl p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="font-semibold">Aylık Takvim</h2>
          <div className="flex gap-2">
            <button
              onClick={() => {
                const [y, m] = currentMonth.split("-").map(Number);
                const prev = new Date(y, m - 2, 1);
                setCurrentMonth(`${prev.getFullYear()}-${String(prev.getMonth() + 1).padStart(2, "0")}`);
              }}
              className="px-3 py-1 text-sm border border-border rounded-lg hover:bg-secondary"
            >
              ←
            </button>
            <span className="px-3 py-1 text-sm font-medium">
              {new Date(year, month - 1).toLocaleDateString("tr-TR", { month: "long", year: "numeric" })}
            </span>
            <button
              onClick={() => {
                const [y, m] = currentMonth.split("-").map(Number);
                const next = new Date(y, m, 1);
                setCurrentMonth(`${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, "0")}`);
              }}
              className="px-3 py-1 text-sm border border-border rounded-lg hover:bg-secondary"
            >
              →
            </button>
          </div>
        </div>

        <div className="grid grid-cols-7 gap-1">
          {["Paz", "Pzt", "Sal", "Çar", "Per", "Cum", "Cmt"].map((d) => (
            <div key={d} className="text-center text-xs font-medium text-muted-foreground py-2">
              {d}
            </div>
          ))}
          {Array.from({ length: firstDay }).map((_, i) => (
            <div key={`empty-${i}`} />
          ))}
          {Array.from({ length: daysInMonth }, (_, i) => {
            const day = i + 1;
            const dateStr = `${currentMonth}-${String(day).padStart(2, "0")}`;
            const isToday = dateStr === today;

            const status = monthStatusMap.get(`${user?.id}-${dateStr}`);

            return (
              <div
                key={day}
                className={`aspect-square rounded-lg p-1 text-xs border ${
                  isToday ? "border-blue-500 bg-blue-50 dark:bg-blue-950/20" : "border-border"
                } ${status ? STATUS_COLORS[status] : "text-muted-foreground"}`}
              >
                <div className="font-medium">{day}</div>
                {status && (
                  <div className="text-[10px] leading-tight mt-0.5">
                    {status === "OFFICE" ? "🏢" : status === "REMOTE" ? "🏠" : status === "LEAVE" ? "🌴" : "🤒"}
                  </div>
                )}
              </div>
            );
          })}
        </div>

        <div className="flex gap-4 mt-4 text-xs text-muted-foreground">
          <span>🏢 Ofis</span>
          <span>🏠 Remote</span>
          <span>🌴 İzin</span>
          <span>🤒 Hasta</span>
        </div>
      </div>
    </div>
  );
}
