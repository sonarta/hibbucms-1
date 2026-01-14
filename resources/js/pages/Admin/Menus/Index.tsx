"use client"

import { useState, useEffect } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion"
import {
  Plus,
  Trash,
  ChevronUp,
  ChevronDown,
  LinkIcon,
  FileText,
  Home,
  ExternalLink,
  GripVertical,
} from "lucide-react"
import AppLayout from "@/layouts/app-layout"
import { Head, useForm, router } from "@inertiajs/react"
import { ReactSortable } from "react-sortablejs"

interface MenuItem {
  id: string | number;
  title: string;
  url: string;
  type: 'custom' | 'page' | 'post' | 'home';
  target?: '_self' | '_blank';
  order: number;
  parent_id?: number | null;
  children: MenuItem[];
}

interface Menu {
  id: string | number;
  name: string;
  location: string;
  description?: string;
  is_active: boolean;
  items: MenuItem[];
}

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

// Definisikan interface untuk event sortable dengan tipe yang kompatibel
interface SortableEvent {
  from: HTMLElement;
  to: HTMLElement;
  oldIndex?: number;
  newIndex?: number;
  item: HTMLElement;
  clone: HTMLElement;
}

// Interface untuk data update yang dikirim ke server
interface UpdateItemData {
  id: string | number;
  parent_id: string | number | null;
  order: number;
  [key: string]: string | number | null | undefined; // Untuk kompatibilitas dengan FormDataConvertible
}

export default function MenuBuilderPage({ menus: initialMenus, pages, posts }: Props) {
  const [activeMenu, setActiveMenu] = useState<string | number>(initialMenus[0]?.id || '')
  const [menuItems, setMenuItems] = useState<MenuItem[]>(initialMenus.find(menu => menu.id === activeMenu)?.items || [])
  const [customLink, setCustomLink] = useState({
    title: '',
    url: ''
  })

  const { data, setData, post, put, processing } = useForm({
    name: initialMenus.find(menu => menu.id === activeMenu)?.name || '',
    location: initialMenus.find(menu => menu.id === activeMenu)?.location || 'header',
    description: initialMenus.find(menu => menu.id === activeMenu)?.description || '',
    is_active: Boolean(initialMenus.find(menu => menu.id === activeMenu)?.is_active)
  })

  useEffect(() => {
    const selectedMenu = initialMenus.find(menu => menu.id.toString() === activeMenu.toString())
    if (selectedMenu) {
      setMenuItems(selectedMenu.items || [])
      setData({
        name: selectedMenu.name,
        location: selectedMenu.location,
        description: selectedMenu.description || '',
        is_active: Boolean(selectedMenu.is_active)
      })
    }
  }, [initialMenus, activeMenu, setData])

  const handleMenuChange = (menuId: string) => {
    setActiveMenu(menuId)
  }

  const addMenuItem = (type: string, item: Page | Post | { title: string; url: string }) => {
    if (type === 'custom' && (!customLink.title || !customLink.url)) {
      alert('Mohon isi judul dan URL untuk link kustom')
      return
    }

    const url = type === 'custom' ? customLink.url :
      type === 'page' ? `/pages/${(item as Page).slug}` :
        type === 'post' ? `/posts/${(item as Post).slug}` : '/'

    const title = type === 'custom' ? customLink.title : (item as Page | Post).title

    router.post(route('admin.menus.items.store', activeMenu), {
      title,
      url,
      type,
      target: '_self',
      order: menuItems.length
    }, {
      preserveScroll: true
    })

    if (type === 'custom') {
      setCustomLink({ title: '', url: '' })
    }
  }

  const removeMenuItem = (id: string | number) => {
    router.delete(route('admin.menus.items.destroy', id), {
      preserveScroll: true
    })
  }

  const moveItemUp = (index: number) => {
    if (index === 0) return
    const items = [...menuItems]
    const itemsToUpdate = [
      {
        id: items[index].id,
        order: items[index - 1].order,
        parent_id: items[index].parent_id || null
      },
      {
        id: items[index - 1].id,
        order: items[index].order,
        parent_id: items[index - 1].parent_id || null
      }
    ]

    router.post(route('admin.menus.items.reorder', activeMenu), {
      items: itemsToUpdate
    }, {
      preserveScroll: true
    })
  }

  const moveItemDown = (index: number) => {
    if (index === menuItems.length - 1) return
    const items = [...menuItems]
    const itemsToUpdate = [
      {
        id: items[index].id,
        order: items[index + 1].order,
        parent_id: items[index].parent_id || null
      },
      {
        id: items[index + 1].id,
        order: items[index].order,
        parent_id: items[index + 1].parent_id || null
      }
    ]

    router.post(route('admin.menus.items.reorder', activeMenu), {
      items: itemsToUpdate
    }, {
      preserveScroll: true
    })
  }

  const handleCreateMenu = () => {
    post(route('admin.menus.store'))
  }

  const handleUpdateMenu = () => {
    put(route('admin.menus.update', activeMenu))
  }

  const handleUpdateMenuItem = (id: string | number, itemData: { title?: string; url?: string; target?: '_self' | '_blank' }) => {
    const item = menuItems.find(item => item.id === id);
    if (!item) return;

    // Jika hanya mengubah target, kita tetap perlu kirim type
    const updatedData = {
      ...itemData,
      type: item.type
    };

    router.put(route('admin.menus.items.update', id), updatedData, {
      preserveScroll: true
    });
  }

  const handleSortItems = (newItems: MenuItem[]) => {
    try {
      if (!newItems || !Array.isArray(newItems)) {
        console.warn('Invalid items data:', newItems);
        return;
      }

      // Update menuItems state with sorted items
      setMenuItems(newItems);

      // Prepare items for API update with the same parent_id as sebelumnya
      const itemsToUpdate = newItems.map((item, index) => ({
        id: item.id,
        order: index,
        parent_id: item.parent_id || null
      }));

      // Send to backend
      router.post(route('admin.menus.items.reorder', activeMenu), {
        items: itemsToUpdate
      }, {
        preserveScroll: true
      });
    } catch (error) {
      console.error('Error during sort items:', error);
    }
  };

  // Function to handle when an item is moved between different lists (parent-child)
  const handleMoveItem = (evt: SortableEvent) => {
    // Jika ada event onAdd
    if (evt.from !== evt.to) {
      try {
        // Mendapatkan item yang dipindahkan
        const oldIndex = evt.oldIndex !== undefined ? evt.oldIndex : -1;
        const newIndex = evt.newIndex !== undefined ? evt.newIndex : -1;

        // Periksa apakah index valid
        if (oldIndex < 0) {
          console.warn('Invalid oldIndex:', oldIndex);
          return;
        }

        // Mendapatkan referensi ke item yang dipindahkan
        const fromListId = evt.from.dataset.listId || 'main';
        const toListId = evt.to.dataset.listId || 'main';

        // Item yang dipindahkan
        let movedItem: MenuItem | null = null;
        let parentItem: MenuItem | null = null;

        // Jika dari main list
        if (fromListId === 'main') {
          movedItem = menuItems[oldIndex];
        } else {
          // Jika dari child list, cari parent item berdasarkan parentId
          const parentId = evt.from.dataset.parentId;
          if (parentId) {
            parentItem = menuItems.find(item => item.id.toString() === parentId) || null;
            if (parentItem && parentItem.children && oldIndex < parentItem.children.length) {
              movedItem = parentItem.children[oldIndex];
            }
          }
        }

        if (!movedItem) {
          console.warn('Item yang dipindahkan tidak ditemukan');
          return;
        }

        let updateData: UpdateItemData | null = null;

        // Jika dipindahkan ke child list
        if (toListId !== 'main' && evt.to.dataset.parentId) {
          const newParentId = evt.to.dataset.parentId;
          updateData = {
            id: movedItem.id,
            parent_id: newParentId,
            order: newIndex
          };
        }
        // Jika dipindahkan ke main list
        else if (toListId === 'main') {
          updateData = {
            id: movedItem.id,
            parent_id: null,
            order: newIndex
          };

          // Update state lokal saat submenu menjadi menu utama
          if (fromListId !== 'main' && parentItem) {
            // Buat salinan dari menuItems
            const updatedMenuItems = [...menuItems];

            // Hapus item dari children parent
            const parentIndex = updatedMenuItems.findIndex(item => item.id === parentItem!.id);
            if (parentIndex !== -1) {
              updatedMenuItems[parentIndex].children = updatedMenuItems[parentIndex].children.filter(
                child => child.id !== movedItem!.id
              );
            }

            // Tambahkan item ke menu utama dengan parent_id null
            const updatedItem = { ...movedItem, parent_id: null };

            // Sisipkan di posisi yang tepat (newIndex)
            updatedMenuItems.splice(newIndex, 0, updatedItem);

            // Update state
            setMenuItems(updatedMenuItems);
          }
        }

        if (updateData) {
          console.log('Updating menu item:', updateData);
          // Kirim ke backend
          router.post(route('admin.menus.items.reorder', activeMenu), {
            items: [updateData]
          }, {
            preserveScroll: true
          });
        }
      } catch (error) {
        console.error('Error during drag and drop:', error);
      }
    }
  };

  // Function to render a recursive menu item
  const renderMenuItem = (item: MenuItem, index: number, items: MenuItem[]) => (
    <div
      key={item.id}
      className="flex flex-col border rounded-md bg-card hover:bg-accent/50 transition-colors mb-2"
      data-id={item.id.toString()}
    >
      <div className="flex items-center gap-2 p-3">
        <div className="text-muted-foreground cursor-move">
          <GripVertical className="h-5 w-5" />
        </div>
        <Accordion type="single" collapsible className="flex-1">
          <AccordionItem value={item.id.toString()} className="border-none">
            <div className="flex items-center">
              {item.type === "page" && <FileText className="h-4 w-4 mr-2 text-muted-foreground" />}
              {item.type === "post" && <FileText className="h-4 w-4 mr-2 text-muted-foreground" />}
              {item.type === "custom" && <LinkIcon className="h-4 w-4 mr-2 text-muted-foreground" />}
              {item.type === "home" && <Home className="h-4 w-4 mr-2 text-muted-foreground" />}
              <span className="font-medium">{item.title}</span>
              <AccordionTrigger className="ml-auto" />
            </div>
            <AccordionContent>
              <div className="space-y-3 pt-2">
                <div className="space-y-1">
                  <Label htmlFor={`item-${item.id}-label`}>Label Navigasi</Label>
                  <Input
                    id={`item-${item.id}-label`}
                    defaultValue={item.title}
                    onChange={(e) => handleUpdateMenuItem(item.id, { title: e.target.value })}
                  />
                </div>
                <div className="space-y-1">
                  <Label htmlFor={`item-${item.id}-url`}>URL</Label>
                  <Input
                    id={`item-${item.id}-url`}
                    defaultValue={item.url}
                    onChange={(e) => handleUpdateMenuItem(item.id, { url: e.target.value })}
                  />
                </div>
                <div className="flex items-center gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleUpdateMenuItem(item.id, { target: item.target === '_blank' ? '_self' : '_blank' })}
                  >
                    <ExternalLink className="h-3.5 w-3.5 mr-1" />
                    {item.target === '_blank' ? 'Buka di tab yang sama' : 'Buka di tab baru'}
                  </Button>
                </div>
              </div>
            </AccordionContent>
          </AccordionItem>
        </Accordion>
        <div className="flex items-center gap-1">
          <Button variant="ghost" size="icon" onClick={() => moveItemUp(index)} disabled={index === 0}>
            <ChevronUp className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            onClick={() => moveItemDown(index)}
            disabled={index === items.length - 1}
          >
            <ChevronDown className="h-4 w-4" />
          </Button>
          <Button variant="ghost" size="icon" onClick={() => removeMenuItem(item.id)}>
            <Trash className="h-4 w-4" />
          </Button>
        </div>
      </div>

      {/* Dropzone for children - always render this to allow dropping */}
      <div className="pl-8 pr-4 pb-4">
        <ReactSortable
          list={item.children || []}
          setList={(newChildren) => {
            // Gunakan setTimeout untuk menghindari loop yang terlalu cepat
            setTimeout(() => {
              try {
                if (!newChildren || !Array.isArray(newChildren)) {
                  console.warn('Invalid children data:', newChildren);
                  return;
                }

                const newMenuItems = [...menuItems];
                const itemIndex = newMenuItems.findIndex(i => i.id === item.id);
                if (itemIndex !== -1) {
                  newMenuItems[itemIndex].children = newChildren;
                  setMenuItems(newMenuItems);

                  // Update backend with new order
                  const itemsToUpdate = newChildren.map((child, idx) => ({
                    id: child.id,
                    order: idx,
                    parent_id: item.id
                  }));

                  if (itemsToUpdate.length > 0) {
                    router.post(route('admin.menus.items.reorder', activeMenu), {
                      items: itemsToUpdate
                    }, {
                      preserveScroll: true
                    });
                  }
                }
              } catch (error) {
                console.error('Error updating nested sortable:', error);
              }
            }, 10);
          }}
          group="nested-menu"
          animation={150}
          fallbackOnBody={true}
          swapThreshold={0.65}
          ghostClass="bg-accent/30"
          handle=".cursor-move"
          className={`space-y-2 ${item.children && item.children.length > 0 ? '' : 'empty-dropzone'}`}
          data-parent-id={item.id}
          data-list-id={`child-${item.id}`}
          emptyInsertThreshold={10}
          forceFallback={true}
          dragClass="opacity-70"
          // @ts-expect-error - hoverClass tidak ada di tipe ReactSortable tetapi bekerja dengan Sortablejs
          hoverClass="dropzone-hover"
          delay={100}
          delayOnTouchOnly={true}
          scroll={true}
          scrollSensitivity={30}
          bubbleScroll={true}
          onAdd={(evt) => {
            try {
              // Cek jika item dari main list dipindahkan ke child list
              const newIndex = evt.newIndex !== undefined ? evt.newIndex : 0;
              const itemId = evt.item.getAttribute('data-id');
              const toParentId = item.id.toString();

              console.log('onAdd event triggered in child list', {
                itemId,
                toParentId,
                newIndex,
                from: evt.from.dataset.listId,
                to: evt.to.dataset.listId
              });

              if (itemId && evt.from.dataset.listId === 'main') {
                // Item dari main list, kirim update ke server
                router.post(route('admin.menus.items.reorder', activeMenu), {
                  items: [{
                    id: itemId,
                    parent_id: toParentId,
                    order: newIndex
                  }]
                }, {
                  preserveScroll: true,
                  onSuccess: () => {
                    // Force reload data untuk memastikan UI terupdate
                    setTimeout(() => {
                      window.location.reload();
                    }, 100);
                  }
                });
              }
            } catch (error) {
              console.error('Error handling onAdd for child list:', error);
            }
          }}
        >
          {item.children && item.children.map((child) => (
            <div
              key={child.id}
              className="flex items-center gap-2 p-3 border rounded-md bg-card hover:bg-accent/50 transition-colors"
              data-id={child.id.toString()}
            >
              <div className="text-muted-foreground cursor-move">
                <GripVertical className="h-5 w-5" />
              </div>
              <Accordion type="single" collapsible className="flex-1">
                <AccordionItem value={child.id.toString()} className="border-none">
                  <div className="flex items-center">
                    {child.type === "page" && <FileText className="h-4 w-4 mr-2 text-muted-foreground" />}
                    {child.type === "post" && <FileText className="h-4 w-4 mr-2 text-muted-foreground" />}
                    {child.type === "custom" && <LinkIcon className="h-4 w-4 mr-2 text-muted-foreground" />}
                    {child.type === "home" && <Home className="h-4 w-4 mr-2 text-muted-foreground" />}
                    <span className="font-medium">{child.title}</span>
                    <AccordionTrigger className="ml-auto" />
                  </div>
                  <AccordionContent>
                    <div className="space-y-3 pt-2">
                      <div className="space-y-1">
                        <Label htmlFor={`item-${child.id}-label`}>Label Navigasi</Label>
                        <Input
                          id={`item-${child.id}-label`}
                          defaultValue={child.title}
                          onChange={(e) => handleUpdateMenuItem(child.id, { title: e.target.value })}
                        />
                      </div>
                      <div className="space-y-1">
                        <Label htmlFor={`item-${child.id}-url`}>URL</Label>
                        <Input
                          id={`item-${child.id}-url`}
                          defaultValue={child.url}
                          onChange={(e) => handleUpdateMenuItem(child.id, { url: e.target.value })}
                        />
                      </div>
                      <div className="flex items-center gap-2">
                        <Button
                          variant="outline"
                          size="sm"
                          onClick={() => handleUpdateMenuItem(child.id, { target: child.target === '_blank' ? '_self' : '_blank' })}
                        >
                          <ExternalLink className="h-3.5 w-3.5 mr-1" />
                          {child.target === '_blank' ? 'Buka di tab yang sama' : 'Buka di tab baru'}
                        </Button>
                      </div>
                    </div>
                  </AccordionContent>
                </AccordionItem>
              </Accordion>
              <Button variant="ghost" size="icon" onClick={() => removeMenuItem(child.id)}>
                <Trash className="h-4 w-4" />
              </Button>
            </div>
          ))}
        </ReactSortable>
      </div>
    </div>
  );

  const breadcrumbs = [
    { title: "Admin", href: route('admin.dashboard') },
    { title: "Menu Builder", href: route('admin.menus.index') }
  ]

  // CSS styles untuk ditambahkan ke <Head>
  const dropzoneCSS = `
    .sortable-ghost {
      opacity: 0.3;
      background: #c8ebfb;
    }

    .sortable-fallback {
      opacity: 0.8;
    }

    .sortable-drag {
      background: #f4f5f7;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .nested-menu-dropzone {
      border: 2px dashed #ccc;
      min-height: 30px;
      background: rgba(0,0,0,0.02);
      margin: 5px 0;
      border-radius: 4px;
    }

    .empty-dropzone {
      min-height: 40px;
      border: 2px dashed #b0bec5;
      background: rgba(176, 190, 197, 0.1);
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 5px 0;
    }

    .empty-dropzone:after {
      content: "Drop item here to create a submenu";
      color: #78909c;
      font-size: 0.75rem;
    }

    .dropzone-hover {
      background: rgba(144, 202, 249, 0.2) !important;
      border-color: #2196f3 !important;
    }
  `;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Menu Builder">
        <style>{dropzoneCSS}</style>
      </Head>
      <div className="p-4">
        <div className="flex justify-between items-center mb-6">
          <div>
            <h1 className="text-2xl font-bold">Menu</h1>
            <p className="text-muted-foreground">Create and manage navigation for your site</p>
          </div>
          <Button onClick={handleUpdateMenu} disabled={processing}>Save Menu</Button>
        </div>

        <div className="grid gap-6 md:grid-cols-12">
          <div className="md:col-span-4 space-y-6">
            <Card>
              <CardHeader>
                <CardTitle>Pengaturan Menu</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="menu-select">Select Menu to Edit</Label>
                  <Select value={activeMenu.toString()} onValueChange={handleMenuChange}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select menu" />
                    </SelectTrigger>
                    <SelectContent>
                      {initialMenus.map((menu) => (
                        <SelectItem key={menu.id} value={menu.id.toString()}>
                          {menu.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="menu-name">Menu Name</Label>
                  <Input
                    id="menu-name"
                    value={data.name}
                    onChange={e => setData('name', e.target.value)}
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="menu-location">Menu Location</Label>
                  <Select value={data.location} onValueChange={(value) => setData('location', value)}>
                    <SelectTrigger>
                      <SelectValue placeholder="Select location" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="header">Header Navigation</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="pt-2">
                  <Button variant="outline" className="w-full" onClick={handleCreateMenu} disabled={processing}>
                    <Plus className="mr-2 h-4 w-4" />
                    Create New Menu
                  </Button>
                </div>
              </CardContent>
            </Card>

            <Tabs defaultValue="pages">
              <TabsList className="grid w-full grid-cols-3">
                <TabsTrigger value="pages">Pages</TabsTrigger>
                <TabsTrigger value="posts">Posts</TabsTrigger>
                <TabsTrigger value="custom">Custom Link</TabsTrigger>
              </TabsList>

              <TabsContent value="pages" className="border rounded-md mt-2">
                <div className="p-4 space-y-4">
                  <div className="relative">
                    <Input placeholder="Search page..." />
                  </div>
                  <div className="space-y-2 max-h-[300px] overflow-y-auto">
                    {pages.map((page) => (
                      <div key={page.id} className="flex items-center justify-between p-2 border rounded-md">
                        <div className="text-sm">{page.title}</div>
                        <Button size="sm" variant="ghost" onClick={() => addMenuItem("page", page)}>
                          <Plus className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                  </div>
                </div>
              </TabsContent>

              <TabsContent value="posts" className="border rounded-md mt-2">
                <div className="p-4 space-y-4">
                  <div className="relative">
                    <Input placeholder="Search post..." />
                  </div>
                  <div className="space-y-2 max-h-[300px] overflow-y-auto">
                    {posts.map((post) => (
                      <div key={post.id} className="flex items-center justify-between p-2 border rounded-md">
                        <div className="text-sm">{post.title}</div>
                        <Button size="sm" variant="ghost" onClick={() => addMenuItem("post", post)}>
                          <Plus className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                  </div>
                </div>
              </TabsContent>

              <TabsContent value="custom" className="border rounded-md mt-2">
                <div className="p-4 space-y-4">
                  <div className="space-y-2">
                    <Label htmlFor="link-text">Link Text</Label>
                    <Input
                      id="link-text"
                      placeholder="Menu Item Text"
                      value={customLink.title}
                      onChange={(e) => setCustomLink(prev => ({ ...prev, title: e.target.value }))}
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="link-url">URL</Label>
                    <Input
                      id="link-url"
                      placeholder="https://example.com"
                      value={customLink.url}
                      onChange={(e) => setCustomLink(prev => ({ ...prev, url: e.target.value }))}
                    />
                  </div>
                  <Button
                    onClick={() => addMenuItem("custom", customLink)}
                    disabled={!customLink.title || !customLink.url}
                  >
                    <Plus className="mr-2 h-4 w-4" />
                    Add to Menu
                  </Button>
                </div>
              </TabsContent>
            </Tabs>
          </div>

          <div className="md:col-span-8">
            <Card>
              <CardHeader>
                <CardTitle>Menu Structure</CardTitle>
                <p className="text-sm text-muted-foreground">Drag and drop to arrange menu order and create submenus</p>
              </CardHeader>
              <CardContent>
                {!activeMenu ? (
                  <div className="text-center py-8 text-muted-foreground">
                    Please select a menu from the dropdown on the left panel.
                  </div>
                ) : menuItems.length === 0 ? (
                  <div className="text-center py-8 text-muted-foreground">
                    Your menu is empty. Add items from the left panel.
                  </div>
                ) : (
                  <div className="space-y-2">
                    <div className="nested-menu-dropzone p-4">
                      <ReactSortable
                        list={menuItems}
                        setList={(newItems) => {
                          // Gunakan setTimeout untuk menghindari loop yang terlalu cepat
                          setTimeout(() => {
                            handleSortItems(newItems);
                          }, 10);
                        }}
                        group="nested-menu"
                        animation={150}
                        fallbackOnBody={true}
                        swapThreshold={0.65}
                        ghostClass="sortable-ghost"
                        chosenClass="sortable-chosen"
                        dragClass="sortable-drag"
                        handle=".cursor-move"
                        className="space-y-2"
                        onEnd={handleMoveItem}
                        onAdd={(evt) => {
                          // Jika ada submenu yang dipindahkan ke menu utama
                          try {
                            const newIndex = evt.newIndex !== undefined ? evt.newIndex : 0;
                            // Cari item yang dipindahkan (dari dataset)
                            const itemId = evt.item.getAttribute('data-id');
                            const fromParentId = evt.from.dataset.parentId;

                            console.log('onAdd event triggered in main list', {
                              itemId,
                              fromParentId,
                              newIndex,
                              from: evt.from.dataset.listId,
                              to: evt.to.dataset.listId
                            });

                            if (itemId && fromParentId) {
                              // Cari item dalam hierarki menu
                              const newMenuItems = [...menuItems];
                              const parentIndex = newMenuItems.findIndex(item => item.id.toString() === fromParentId);

                              console.log('Parent found:', parentIndex, newMenuItems[parentIndex]);

                              if (parentIndex !== -1 && newMenuItems[parentIndex].children) {
                                // Cari item dalam children
                                const childIndex = newMenuItems[parentIndex].children.findIndex(
                                  child => child.id.toString() === itemId
                                );

                                console.log('Child found:', childIndex, newMenuItems[parentIndex].children[childIndex]);

                                if (childIndex !== -1) {
                                  // Ambil item dan hapus dari children
                                  const movedItem = { ...newMenuItems[parentIndex].children[childIndex], parent_id: null };
                                  newMenuItems[parentIndex].children = newMenuItems[parentIndex].children.filter(
                                    child => child.id.toString() !== itemId
                                  );

                                  // Masukkan ke menu utama
                                  newMenuItems.splice(newIndex, 0, movedItem);

                                  console.log('Updated menu items:', newMenuItems);

                                  // Update state dengan timer untuk mengatasi race condition
                                  setTimeout(() => {
                                    setMenuItems(newMenuItems);
                                  }, 50);

                                  // Kirim ke backend dengan delay untuk memastikan state terupdate dulu
                                  setTimeout(() => {
                                    router.post(route('admin.menus.items.reorder', activeMenu), {
                                      items: [{
                                        id: movedItem.id,
                                        parent_id: null,
                                        order: newIndex
                                      }]
                                    }, {
                                      preserveScroll: true,
                                      onSuccess: () => {
                                        // Refresh data dari server setelah update
                                        setTimeout(() => {
                                          window.location.reload();
                                        }, 100);
                                      }
                                    });
                                  }, 100);
                                }
                              }
                            }
                          } catch (error) {
                            console.error('Error handling onAdd for main list:', error);
                          }
                        }}
                        emptyInsertThreshold={10}
                        forceFallback={true}
                        delay={100}
                        delayOnTouchOnly={true}
                        scroll={true}
                        scrollSensitivity={30}
                        bubbleScroll={true}
                        data-list-id="main"
                        // @ts-expect-error - hoverClass tidak ada di tipe ReactSortable tetapi bekerja dengan Sortablejs
                        hoverClass="dropzone-hover"
                      >
                        {menuItems.map((item, index) => renderMenuItem(item, index, menuItems))}
                      </ReactSortable>
                    </div>
                    <div className="mt-4 text-center">
                      <p className="text-xs text-muted-foreground">
                        Tip: Drag and drop menu item to another item to create a submenu. Drag item out to return to the main menu.
                      </p>
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  )
}
