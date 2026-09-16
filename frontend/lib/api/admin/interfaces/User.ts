export interface IUser {
  id: number | undefined;
  first_name: string | undefined;
  last_name: string | undefined;
  name: string | undefined;
  email: string | undefined;
  email_verified_at: string | undefined;
  created_at: string | undefined;
  updated_at: string | undefined;
}

export interface UserSession {
  id?: number;
  first_name?: string;
  last_name?: string;
  name?: string;
  email?: string | null | undefined;
}
