import { prisma } from "@/lib/prisma";
import { getEffectiveUserId, requireAuth, getSessionUser, isAdmin } from "@/lib/auth-helpers";
import { NextResponse } from "next/server";

export async function GET(req: Request) {
  await requireAuth();

  const url = new URL(req.url);
  const startDate = url.searchParams.get("startDate");
  const endDate = url.searchParams.get("endDate");
  const userId = url.searchParams.get("userId");

  const where: any = {};

  if (userId) where.userId = userId;
  if (startDate || endDate) {
    where.date = {};
    if (startDate) where.date.gte = new Date(startDate);
    if (endDate) where.date.lte = new Date(endDate + "T23:59:59.999Z");
  }

  const records = await prisma.attendance.findMany({
    where,
    orderBy: [{ date: "desc" }, { userId: "asc" }],
    include: {
      user: { select: { id: true, name: true, email: true } },
    },
  });

  return NextResponse.json(records);
}

export async function POST(req: Request) {
  const sessionUser = await getSessionUser();
  if (!sessionUser) return NextResponse.json({ error: "Unauthorized" }, { status: 401 });

  const body = await req.json();
  const { date, present, note, userId: targetUserId } = body;

  if (!date) {
    return NextResponse.json({ error: "Tarih gerekli" }, { status: 400 });
  }

  const effectiveUserId = targetUserId && isAdmin(sessionUser) ? targetUserId : sessionUser.id;

  const record = await prisma.attendance.upsert({
    where: { userId_date: { userId: effectiveUserId, date: new Date(date) } },
    update: { present: present ?? true, note: note || "" },
    create: { userId: effectiveUserId, date: new Date(date), present: present ?? true, note: note || "" },
    include: {
      user: { select: { id: true, name: true, email: true } },
    },
  });

  return NextResponse.json(record);
}
