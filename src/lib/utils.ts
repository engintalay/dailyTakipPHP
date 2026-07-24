import { type ClassValue, clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function formatDate(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleDateString("tr-TR", {
    day: "numeric",
    month: "long",
    year: "numeric",
    weekday: "long",
  });
}

export function formatDateShort(date: Date | string): string {
  const d = new Date(date);
  return d.toLocaleDateString("tr-TR", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

export function formatDateOnly(date: Date | string): string {
  const d = new Date(date);
  return d.toISOString().split("T")[0];
}

export function todayString(): string {
  return formatDateOnly(new Date());
}

export function getWeekRange(date: Date = new Date()): { start: Date; end: Date } {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  const start = new Date(d.setDate(diff));
  start.setHours(0, 0, 0, 0);
  const end = new Date(start);
  end.setDate(end.getDate() + 6);
  end.setHours(23, 59, 59, 999);
  return { start, end };
}

export function getMonthRange(date: Date = new Date()): { start: Date; end: Date } {
  const start = new Date(date.getFullYear(), date.getMonth(), 1);
  const end = new Date(date.getFullYear(), date.getMonth() + 1, 0, 23, 59, 59, 999);
  return { start, end };
}

export const STATUS_LABELS: Record<string, string> = {
  OFFICE: "Ofis",
  REMOTE: "Remote",
  LEAVE: "İzin",
  SICK: "Hasta",
};

export const STATUS_COLORS: Record<string, string> = {
  OFFICE: "bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400",
  REMOTE: "bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400",
  LEAVE: "bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400",
  SICK: "bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400",
};

export const STATUS_TURKCE: Record<string, string> = {
  OFFICE: "🏢 Ofiste",
  REMOTE: "🏠 Remote",
  LEAVE: "🌴 İzinli",
  SICK: "🤒 Hasta",
};
