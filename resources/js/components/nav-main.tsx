"use client"

import { ChevronRight } from "lucide-react"
import { Link, usePage } from "@inertiajs/react"
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from "@/components/ui/collapsible"
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from "@/components/ui/sidebar"
import { type NavItem } from "@/types"

interface NavMainProps {
  items: Array<NavItem & { items?: Array<{ title: string; href: string }> }>
  label?: string
}

export function NavMain({ items, label }: NavMainProps) {
  const { component } = usePage()

  const isUrlMatching = (itemUrl: string) => {
    // Pastikan itemUrl tidak undefined
    if (!itemUrl) return false

    // Mengambil resource name dari URL
    const getResourceName = (url: string) => {
      const segments = url.split('/')
      // Mengambil segment yang merepresentasikan resource (posts, categories, tags, etc)
      return segments.find(segment =>
        ['dashboard','posts', 'categories', 'tags', 'pages', 'users', 'roles', 'media', 'menus', 'themes', 'plugins', 'settings'].includes(segment)
      )
    }

    const currentRouteName = component.toLowerCase()
    const itemResource = getResourceName(itemUrl)

    if (!itemResource) return false

    // Mencocokkan route name dengan resource
    // Contoh: admin.posts.index, admin.posts.create akan cocok dengan resource 'posts'
    return currentRouteName.includes(itemResource)
  }

  return (
    <SidebarGroup>
      {label && <SidebarGroupLabel>{label}</SidebarGroupLabel>}
      <SidebarMenu>
        {items.map((item) => {
          const isActive = isUrlMatching(item.href)

          if (item.items && item.items.length > 0) {
            const hasActiveChild = item.items.some(subItem => isUrlMatching(subItem.href))
            return (
              <Collapsible key={item.title} asChild className="group/collapsible" defaultOpen={isActive || hasActiveChild}>
                <SidebarMenuItem>
                  <CollapsibleTrigger asChild>
                    <SidebarMenuButton tooltip={item.title} isActive={isActive || hasActiveChild}>
                      {item.icon && <item.icon />}
                      <span>{item.title}</span>
                      <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                    </SidebarMenuButton>
                  </CollapsibleTrigger>
                  <CollapsibleContent>
                    <SidebarMenuSub>
                      {item.items.map((subItem) => (
                        <SidebarMenuSubItem key={subItem.title}>
                          <SidebarMenuSubButton asChild isActive={isUrlMatching(subItem.href)}>
                            <Link href={subItem.href}>
                              <span>{subItem.title}</span>
                            </Link>
                          </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                      ))}
                    </SidebarMenuSub>
                  </CollapsibleContent>
                </SidebarMenuItem>
              </Collapsible>
            )
          }

          return (
            <SidebarMenuItem key={item.title}>
              <SidebarMenuButton asChild tooltip={item.title} isActive={isActive}>
                <Link href={item.href}>
                  {item.icon && <item.icon />}
                  <span>{item.title}</span>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          )
        })}
      </SidebarMenu>
    </SidebarGroup>
  )
}
