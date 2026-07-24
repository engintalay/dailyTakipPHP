import { auth } from "./auth";
import { prisma } from "./prisma";
import type { SessionUser } from "./types";

export async function getSessionUser(): Promise<SessionUser | null> {
  const session = await auth();
  if (!session?.user) return null;
  return session.user as SessionUser;
}

export async function getEffectiveUserId(): Promise<string | null> {
  const sessionUser = await getSessionUser();
  if (!sessionUser) return null;
  return sessionUser.impersonatingUserId || sessionUser.id;
}

export async function getEffectiveUser() {
  const sessionUser = await getSessionUser();
  if (!sessionUser) return null;
  const userId = sessionUser.impersonatingUserId || sessionUser.id;
  return prisma.user.findUnique({ where: { id: userId } });
}

export function isAdmin(user: SessionUser | null): boolean {
  return user?.role === "ADMIN";
}

export async function requireAdmin() {
  const user = await getSessionUser();
  if (!isAdmin(user)) {
    throw new Error("Unauthorized");
  }
  return user!;
}

export async function requireAuth() {
  const user = await getSessionUser();
  if (!user) {
    throw new Error("Unauthorized");
  }
  return user;
}
