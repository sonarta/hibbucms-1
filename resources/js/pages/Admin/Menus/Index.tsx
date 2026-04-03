"use client"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import AppLayout from "@/layouts/app-layout"
import { Head, useForm, router } from "@inertiajs/react"
import MenuSidebar from "./components/MenuSidebar"
import MenuTree from "./components/MenuTree"
import { Menu, MenuItemData, MenuItemUpdatePayload, MenuReorderPayload } from "./components/types"

interface Page {
  id: string | number;
  title: string;
  slug: string;
}

interface Post {
  id: string | number;
  title: string;
  slug: string;
}

interface Props {
  menus: Menu[];
  pages: Page[];
  posts: Post[];
}

export default function MenuBuilderPage({ menus: initialMenus, pages, posts }: Props) {
  const [activeMenu, setActiveMenu] = useState<string>(initialMenus[0]?.id.toString() || '')
  const [menuItems, setMenuItems] = useState<MenuItemData[]>([])

  // Form for Menu Settings (Name, Location)
  const { data, setData, post, put, processing } = useForm({
    name: '',
    location: 'header',
    description: '',
    is_active: true
  })

  // Sync local state when activeMenu changes
  useEffect(() => {
    const selectedMenu = initialMenus.find(menu => menu.id.toString() === activeMenu)
    if (selectedMenu) {
      setMenuItems(selectedMenu.items || [])
      setData({
        name: selectedMenu.name,
        location: selectedMenu.location,
        description: selectedMenu.description || '',
        is_active: Boolean(selectedMenu.is_active)
      })
    } else {
      setMenuItems([])
      setData({
        name: '',
        location: 'header',
        description: '',
        is_active: true
      })
    }
  }, [initialMenus, activeMenu, setData])

  const handleCreateMenu = () => {
    post(route('admin.menus.store'), {
      onSuccess: () => {
        // Ideally prompt the user to select the new menu or auto-select it
        // We assume the backend redirect handles refresh, but we might need to update activeMenu
      }
    })
  }

  const handleUpdateMenu = () => {
    if (!activeMenu) return
    put(route('admin.menus.update', activeMenu))
  }

  const handleAddItems = (type: string, items: (Page | Post | { title: string; url: string })[]) => {
    if (!activeMenu) {
      alert("Please select or create a menu first.")
      return
    }

    if (items.length === 0) {
      return
    }

    const payload = items.map((item) => {
      let url = '/'
      if (type === 'custom' && 'url' in item) {
        url = item.url
      } else if (type === 'page' && 'slug' in item) {
        url = `/pages/${item.slug}`
      } else if (type === 'post' && 'slug' in item) {
        url = `/posts/${item.slug}`
      }
      const itemType =
        type === 'custom'
          ? 'custom'
          : type === 'page'
            ? 'page'
            : type === 'post'
              ? 'post'
              : 'home'
      return {
        title: item.title,
        url,
        type: itemType,
        target: '_self' as const,
      }
    })

    router.post(route('admin.menus.items.bulk-store', activeMenu), { items: payload }, { preserveScroll: true })
  }

  const handleUpdateMenuItem = (id: string | number, itemData: MenuItemUpdatePayload) => {
    // If updating children, we don't send that to 'update' endpoint, that's handled by 'reorder' usually.
    // But here we might receive 'children' from MenuItem component local state update.
    // If 'children' is in itemData, we IGNORE it for the 'update' route (which is for properties like title/url).

    if (itemData.children) {
      // This is just a local state sync from drag-and-drop
      // We need to find the item in our local state and update its children
      // But wait, the MenuTree setList already does this via setMenuItems?
      // Not exactly. Deeply nested setList only updates that specific branch.
      // We need to update the root menuItems state.

      // Actually, the MenuTree component logic was:
      // setItems={(newChildren) => onUpdate(item.id, { children: newChildren })}

      // So we need to handle deep updates here.
      const updateRecursive = (items: MenuItemData[]): MenuItemData[] => {
        return items.map(item => {
          if (item.id === id) {
            return { ...item, ...itemData };
          }
          if (item.children) {
            return { ...item, children: updateRecursive(item.children) };
          }
          return item;
        });
      };

      setMenuItems(prev => updateRecursive(prev));
      return; // Don't send to backend 'update' route if it's just children/structure update
    }

    // For real backend updates (title, url, etc)
    const item = findItemRecursive(menuItems, id);
    if (!item) return;

    // Optimistic update
    const updateRecursive = (items: MenuItemData[]): MenuItemData[] => {
      return items.map(i => {
        if (i.id === id) return { ...i, ...itemData };
        if (i.children) return { ...i, children: updateRecursive(i.children) };
        return i;
      });
    };
    setMenuItems(prev => updateRecursive(prev));

    const updateFields = { ...itemData };
    delete updateFields.children;

    router.put(route('admin.menus.items.update', id), {
      ...updateFields,
      type: item.type,
    }, {
      preserveScroll: true
    });
  }

  // Helper to find item
  const findItemRecursive = (items: MenuItemData[], id: string | number): MenuItemData | null => {
    for (const item of items) {
      if (item.id === id) return item;
      if (item.children) {
        const found = findItemRecursive(item.children, id);
        if (found) return found;
      }
    }
    return null;
  }

  const handleRemoveMenuItem = (id: string | number) => {
    router.delete(route('admin.menus.items.destroy', id), {
      preserveScroll: true
    })
  }

  const handleReorder = (itemsToUpdate: MenuReorderPayload[]) => {
    // This is called when Drag & Drop finishes or Structure changes
    const items = itemsToUpdate.map((row) => ({
      id: row.id,
      order: row.order,
      parent_id: row.parent_id ?? null,
    }));
    router.post(route('admin.menus.items.reorder', activeMenu), { items }, {
      preserveScroll: true
    });
  }

  const breadcrumbs = [
    { title: "Admin", href: route('admin.dashboard') },
    { title: "Menu Builder", href: route('admin.menus.index') }
  ]

  // Styles for drag and drop visuals
  const dropzoneCSS = `
        .sortable-ghost {
            opacity: 0.3;
            background: hsl(var(--accent));
            border: 2px dashed hsl(var(--primary));
        }
        .sortable-drag {
            cursor: grabbing;
            opacity: 1 !important;
            background: hsl(var(--background));
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transform: scale(1.02);
        }
        .empty-dropzone {
            min-height: 48px;
            border: 2px dashed hsl(var(--border));
            background: hsl(var(--muted)/0.3);
            border-radius: var(--radius);
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .empty-dropzone:after {
            content: "Drop submenu items here";
            font-size: 0.8rem;
            color: hsl(var(--muted-foreground));
        }
    `;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Menu Builder">
        <style>{dropzoneCSS}</style>
      </Head>
      <div className="p-4 md:p-6 space-y-6">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <div>
            <h1 className="text-2xl font-bold tracking-tight">Menu Builder</h1>
            <p className="text-muted-foreground">Manage your site navigation structure.</p>
          </div>
          <Button onClick={handleUpdateMenu} disabled={processing || !activeMenu}>
            Save Menu Changes
          </Button>
        </div>

        <div className="grid gap-6 md:grid-cols-12 items-start">
          <div className="md:col-span-4 lg:col-span-3">
            <MenuSidebar
              menus={initialMenus}
              activeMenu={activeMenu}
              setActiveMenu={setActiveMenu}
              pages={pages}
              posts={posts}
              onAddItems={handleAddItems}
              onCreateMenu={handleCreateMenu}
              menuData={data}
              setMenuData={setData}
              isProcessing={processing}
            />
          </div>

          <div className="md:col-span-8 lg:col-span-9">
            <Card className="min-h-[500px]">
              <CardHeader>
                <CardTitle>Menu Structure</CardTitle>
                <p className="text-sm text-muted-foreground">
                  Drag items to reorder. Drag right to create submenus.
                </p>
              </CardHeader>
              <CardContent>
                {!activeMenu ? (
                  <div className="flex flex-col items-center justify-center py-12 text-center text-muted-foreground border-2 border-dashed rounded-lg bg-muted/10">
                    <p>Select a menu to start editing.</p>
                  </div>
                ) : menuItems.length === 0 ? (
                  <div className="flex flex-col items-center justify-center py-12 text-center text-muted-foreground border-2 border-dashed rounded-lg bg-muted/10">
                    <p>This menu is empty.</p>
                    <p className="text-sm mt-1">Add items from the sidebar to get started.</p>
                  </div>
                ) : (
                  <MenuTree
                    items={menuItems}
                    setItems={setMenuItems}
                    onUpdateItem={handleUpdateMenuItem}
                    onRemoveItem={handleRemoveMenuItem}
                    activeMenu={activeMenu}
                    onReorder={handleReorder}
                    className="py-2"
                  />
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  )
}
