import { prisma } from "@/lib/prisma";
import { requireAdmin } from "@/lib/auth-helpers";
import { hash } from "bcryptjs";
import { NextResponse } from "next/server";

export async function PATCH(req: Request, { params }: { params: Promise<{ id: string }> }) {
  await requireAdmin();
  const { id } = await params;
  const body = await req.json();

  const updateData: Record<string, any> = {};

  if (body.name) updateData.name = body.name;
  if (body.email) updateData.email = body.email;
  if (body.role) updateData.role = body.role;
  if (typeof body.isActive === "boolean") updateData.isActive = body.isActive;
  if (body.password) {
    updateData.passwordHash = await hash(body.password, 12);
  }

  const user = await prisma.user.update({
    where: { id },
    data: updateData,
    select: { id: true, name: true, email: true, role: true, isActive: true, createdAt: true },
  });

  return NextResponse.json(user);
}

export async function DELETE(req: Request, { params }: { params: Promise<{ id: string }> }) {
  await requireAdmin();
  const { id } = await params;

  await prisma.user.delete({ where: { id } });

  return NextResponse.json({ success: true });
}
