import { prisma } from "@/lib/prisma";
import { getEffectiveUserId, requireAuth, getSessionUser, isAdmin } from "@/lib/auth-helpers";
import { NextResponse } from "next/server";

export async function GET(req: Request) {
  await requireAuth();

  const url = new URL(req.url);
  const date = url.searchParams.get("date");
  const userId = url.searchParams.get("userId");
  const startDate = url.searchParams.get("startDate");
  const endDate = url.searchParams.get("endDate");

  const where: any = {};

  if (userId) where.userId = userId;

  if (date) {
    const d = new Date(date);
    where.date = { gte: d, lt: new Date(d.getTime() + 86400000) };
  }

  if (startDate || endDate) {
    where.date = {};
    if (startDate) where.date.gte = new Date(startDate);
    if (endDate) where.date.lte = new Date(endDate + "T23:59:59.999Z");
  }

  const statuses = await prisma.dailyStatus.findMany({
    where,
    orderBy: { date: "desc" },
    include: {
      user: { select: { id: true, name: true, email: true } },
    },
  });

  return NextResponse.json(statuses);
}

export async function POST(req: Request) {
  const sessionUser = await getSessionUser();
  if (!sessionUser) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const body = await req.json();
  const { date, endDate, type, note, userId: targetUserId } = body;

  if (!date || !type) {
    return NextResponse.json({ error: "Tarih ve durum gerekli" }, { status: 400 });
  }

  const validTypes = ["OFFICE", "REMOTE", "LEAVE", "SICK"];
  if (!validTypes.includes(type)) {
    return NextResponse.json({ error: "Geçersiz durum" }, { status: 400 });
  }

  const effectiveUserId = targetUserId && isAdmin(sessionUser) ? targetUserId : sessionUser.id;

  const start = new Date(date);
  const end = endDate ? new Date(endDate) : start;

  const dates: Date[] = [];
  for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
    dates.push(new Date(d));
  }

  const results = await Promise.all(
    dates.map((dt) =>
      prisma.dailyStatus.upsert({
        where: { userId_date: { userId: effectiveUserId, date: dt } },
        update: { type, note: note || "" },
        create: { userId: effectiveUserId, date: dt, type, note: note || "" },
        include: {
          user: { select: { id: true, name: true, email: true } },
        },
      })
    )
  );

  return NextResponse.json(results);
}
