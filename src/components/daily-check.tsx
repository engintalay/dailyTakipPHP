"use client";

import { useEffect, useState } from "react";
import { useSession } from "next-auth/react";
import { useRouter } from "next/navigation";
import { formatDateOnly } from "@/lib/utils";
import type { SessionUser } from "@/lib/types";

export function DailyCheckBanner() {
  const { data: session } = useSession();
  const user = session?.user as SessionUser | undefined;
  const router = useRouter();
  const [needsNote, setNeedsNote] = useState(false);
  const [needsStatus, setNeedsStatus] = useState(false);

  useEffect(() => {
    if (!user) return;
    checkToday();
  }, [user]);

  async function checkToday() {
    const today = formatDateOnly(new Date());
    const userId = user!.impersonatingUserId || user!.id;

    const [notesRes, statusRes] = await Promise.all([
      fetch(`/api/daily-notes?startDate=${today}&endDate=${today}&userId=${userId}`),
      fetch(`/api/status?date=${today}&userId=${userId}`),
    ]);

    const notes = await notesRes.json();
    const statuses = await statusRes.json();

    setNeedsNote(notes.length === 0);
    setNeedsStatus(statuses.length === 0);
  }

  if (!user) return null;
  if (!needsNote && !needsStatus) return null;

  return (
    <div className="bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-6">
      <div className="flex items-center justify-between">
        <div className="text-sm text-amber-800 dark:text-amber-300">
          ⏰ Bugün için yapılacaklar:
          {needsNote && needsStatus
            ? " Daily not girmedin ve durumunu belirtmedin."
            : needsNote
            ? " Daily not girmedin."
            : " Durumunu belirtmedin (ofis/remote/izin)."}
        </div>
        <div className="flex gap-2">
          {needsNote && (
            <button
              onClick={() => router.push("/daily")}
              className="px-3 py-1 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700"
            >
              Not Ekle
            </button>
          )}
          {needsStatus && (
            <button
              onClick={() => router.push("/status")}
              className="px-3 py-1 text-xs bg-amber-600 text-white rounded-lg hover:bg-amber-700"
            >
              Durum Belirt
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
