import { LucideIcon } from "lucide-react";
import type { Config } from 'ziggy-js';

export interface Page {
  id: number;
  title: string;
  slug: string;
  content: string;
  featured_image: string | null;
  meta_description: string | null;
  meta_keywords: string | null;
  status: 'draft' | 'published';
  order: number;
  user: {
    id: number;
    name: string;
  };
  created_at: string;
  updated_at: string;
}

export interface NavSubItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    items?: NavSubItem[];
}

export interface BreadcrumbItem {
    title: string;
    href?: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar: string;
}

export interface Auth {
    user: User;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

