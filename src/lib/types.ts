import type { User as DbUser } from "@prisma/client";

export type Role = "ADMIN" | "MEMBER";

export type StatusType = "OFFICE" | "REMOTE" | "LEAVE" | "SICK";

export type SessionUser = {
  id: string;
  name: string;
  email: string;
  role: Role;
  impersonatingUserId?: string;
};

export type SafeUser = Pick<DbUser, "id" | "name" | "email" | "role" | "isActive" | "createdAt">;
