import { useState } from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Checkbox } from "@/components/ui/checkbox"
import { Plus, Search } from "lucide-react"

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

interface Menu {
    id: string | number;
    name: string;
    location: string;
}

interface MenuSidebarProps {
    menus: Menu[];
    activeMenu: string;
    setActiveMenu: (id: string) => void;
    pages: Page[];
    posts: Post[];
    onAddItems: (type: string, items: any[]) => void;
    onCreateMenu: () => void;
    menuData: {
        name: string;
        location: string;
    };
    setMenuData: (key: string, value: any) => void;
    isProcessing: boolean;
}

export default function MenuSidebar({
    menus,
    activeMenu,
    setActiveMenu,
    pages,
    posts,
    onAddItems,
    onCreateMenu,
    menuData,
    setMenuData,
    isProcessing
}: MenuSidebarProps) {
    const [selectedPages, setSelectedPages] = useState<string[]>([])
    const [selectedPosts, setSelectedPosts] = useState<string[]>([])
    const [pageSearch, setPageSearch] = useState("")
    const [postSearch, setPostSearch] = useState("")
    const [customLink, setCustomLink] = useState({ title: '', url: '' })

    const filteredPages = pages.filter(page =>
        page.title.toLowerCase().includes(pageSearch.toLowerCase())
    )

    const filteredPosts = posts.filter(post =>
        post.title.toLowerCase().includes(postSearch.toLowerCase())
    )

    const handleSelectPage = (id: string) => {
        setSelectedPages(prev =>
            prev.includes(id) ? prev.filter(p => p !== id) : [...prev, id]
        )
    }

    const handleSelectPost = (id: string) => {
        setSelectedPosts(prev =>
            prev.includes(id) ? prev.filter(p => p !== id) : [...prev, id]
        )
    }

    const handleAddPages = () => {
        const itemsToAdd = pages.filter(p => selectedPages.includes(p.id.toString()))
        onAddItems('page', itemsToAdd)
        setSelectedPages([])
    }

    const handleAddPosts = () => {
        const itemsToAdd = posts.filter(p => selectedPosts.includes(p.id.toString()))
        onAddItems('post', itemsToAdd)
        setSelectedPosts([])
    }

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Pengaturan Menu</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="menu-select">Select Menu to Edit</Label>
                        <Select value={activeMenu.toString()} onValueChange={setActiveMenu}>
                            <SelectTrigger>
                                <SelectValue placeholder="Select menu" />
                            </SelectTrigger>
                            <SelectContent>
                                {menus.map((menu) => (
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
                            value={menuData.name}
                            onChange={e => setMenuData('name', e.target.value)}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="menu-location">Menu Location</Label>
                        <Select
                            value={menuData.location}
                            onValueChange={(value) => setMenuData('location', value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Select location" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="header">Header Navigation</SelectItem>
                                <SelectItem value="footer">Footer Menu</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="pt-2">
                        <Button
                            variant="outline"
                            className="w-full"
                            onClick={onCreateMenu}
                            disabled={isProcessing}
                        >
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
                    <TabsTrigger value="custom">Custom</TabsTrigger>
                </TabsList>

                <TabsContent value="pages" className="border rounded-md mt-2 bg-card">
                    <div className="p-4 space-y-4">
                        <div className="relative">
                            <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search page..."
                                className="pl-8"
                                value={pageSearch}
                                onChange={(e) => setPageSearch(e.target.value)}
                            />
                        </div>
                        <div className="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                            {filteredPages.map((page) => (
                                <div key={page.id} className="flex items-center space-x-2 p-1 hover:bg-accent rounded-sm">
                                    <Checkbox
                                        id={`page-${page.id}`}
                                        checked={selectedPages.includes(page.id.toString())}
                                        onCheckedChange={() => handleSelectPage(page.id.toString())}
                                    />
                                    <Label
                                        htmlFor={`page-${page.id}`}
                                        className="flex-1 cursor-pointer py-1 font-normal"
                                    >
                                        {page.title}
                                    </Label>
                                </div>
                            ))}
                            {filteredPages.length === 0 && (
                                <p className="text-sm text-center text-muted-foreground py-4">
                                    No pages found
                                </p>
                            )}
                        </div>
                        <Button
                            className="w-full"
                            size="sm"
                            disabled={selectedPages.length === 0 || isProcessing}
                            onClick={handleAddPages}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add to Menu ({selectedPages.length})
                        </Button>
                    </div>
                </TabsContent>

                <TabsContent value="posts" className="border rounded-md mt-2 bg-card">
                    <div className="p-4 space-y-4">
                        <div className="relative">
                            <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                            <Input
                                placeholder="Search post..."
                                className="pl-8"
                                value={postSearch}
                                onChange={(e) => setPostSearch(e.target.value)}
                            />
                        </div>
                        <div className="space-y-2 max-h-[300px] overflow-y-auto pr-1">
                            {filteredPosts.map((post) => (
                                <div key={post.id} className="flex items-center space-x-2 p-1 hover:bg-accent rounded-sm">
                                    <Checkbox
                                        id={`post-${post.id}`}
                                        checked={selectedPosts.includes(post.id.toString())}
                                        onCheckedChange={() => handleSelectPost(post.id.toString())}
                                    />
                                    <Label
                                        htmlFor={`post-${post.id}`}
                                        className="flex-1 cursor-pointer py-1 font-normal"
                                    >
                                        {post.title}
                                    </Label>
                                </div>
                            ))}
                            {filteredPosts.length === 0 && (
                                <p className="text-sm text-center text-muted-foreground py-4">
                                    No posts found
                                </p>
                            )}
                        </div>
                        <Button
                            className="w-full"
                            size="sm"
                            disabled={selectedPosts.length === 0 || isProcessing}
                            onClick={handleAddPosts}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add to Menu ({selectedPosts.length})
                        </Button>
                    </div>
                </TabsContent>

                <TabsContent value="custom" className="border rounded-md mt-2 bg-card">
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
                            className="w-full"
                            onClick={() => {
                                onAddItems("custom", [customLink])
                                setCustomLink({ title: '', url: '' })
                            }}
                            disabled={!customLink.title || !customLink.url || isProcessing}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Add to Menu
                        </Button>
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    )
}
