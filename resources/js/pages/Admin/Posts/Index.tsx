import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import { Plus, ImageIcon, FileText } from 'lucide-react';
import ReactDOM from 'react-dom/client';

interface Category {
    id: number;
    name: string;
}

interface Post {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    content: string;
    featured_image: string;
    featured_image_url?: string;
    status: 'draft' | 'published';
    published_at: string | null;
    category: Category;
    tags: {
        id: number;
        name: string;
        color: string;
    }[];
    created_at: string;
    updated_at: string;
}

interface Props {
    posts: {
        data: Post[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search: string;
        status: string;
        category: string;
    };
}

const breadcrumbs = [
    {
        title: 'Dashboard',
        href: route('admin.dashboard'),
    },
    {
        title: 'Posts',
    },
];

export default function Index({ posts, filters = { search: '', status: 'all', category: 'all' } }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [category, setCategory] = useState(filters.category);

    // Mendapatkan unique categories dari posts
    const uniqueCategories = Array.from(
        new Map(
            posts.data.map(post => [post.category.id, post.category])
        ).values()
    );

    const handleSearch = (value: string) => {
        setSearch(value);
        router.get(
            route('admin.posts.index'),
            { search: value, status, category },
            { preserveState: true }
        );
    };

    const handleStatusChange = (value: string) => {
        setStatus(value);
        router.get(
            route('admin.posts.index'),
            { search, status: value, category },
            { preserveState: true }
        );
    };

    const handleCategoryChange = (value: string) => {
        setCategory(value);
        router.get(
            route('admin.posts.index'),
            { search, status, category: value },
            { preserveState: true }
        );
    };

    const getImageUrl = (post: Post): string | undefined => {
        if (!post.featured_image) return undefined;

        if (post.featured_image_url) {
            try {
                const url = new URL(post.featured_image_url);
                if (url.origin === window.location.origin) {
                    return url.pathname;
                }
                return post.featured_image_url;
            } catch {
                return post.featured_image_url;
            }
        }

        if (post.featured_image.startsWith('http://') || post.featured_image.startsWith('https://')) {
            try {
                const url = new URL(post.featured_image);
                if (url.origin === window.location.origin) {
                    return url.pathname;
                }
                return post.featured_image;
            } catch {
                return post.featured_image;
            }
        }

        return post.featured_image.startsWith('/') ? post.featured_image : `/${post.featured_image}`;
    };

    const handleImageError = (e: React.SyntheticEvent<HTMLImageElement, Event>) => {
        const target = e.target as HTMLImageElement;
        target.onerror = null;
        target.style.display = 'none';

        const parent = target.parentElement;
        if (parent) {
            parent.classList.add('flex', 'items-center', 'justify-center');
            const iconContainer = document.createElement('div');
            iconContainer.className = 'w-6 h-6 text-gray-400';
            parent.appendChild(iconContainer);

            const root = ReactDOM.createRoot(iconContainer);
            root.render(<ImageIcon />);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Posts" />

            <div className="p-4">
                <div className="flex justify-between items-center mb-4">
                    <div>
                        <h1 className="text-2xl font-bold">Posts</h1>
                        <p className="text-sm text-gray-500">Manage your blog posts here</p>
                    </div>
                    <Link href={route('admin.posts.create')}>
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Create Post
                        </Button>
                    </Link>
                </div>

                <div className="rounded-lg shadow border p-4 mb-4">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <Input
                                placeholder="Search post..."
                                value={search}
                                onChange={(e) => handleSearch(e.target.value)}
                                className="w-full"
                            />
                        </div>
                        <div>
                            <Select value={status} onValueChange={handleStatusChange}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Filter by status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Status</SelectItem>
                                    <SelectItem value="draft">Draft</SelectItem>
                                    <SelectItem value="published">Published</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Select value={category} onValueChange={handleCategoryChange}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Filter by category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Categories</SelectItem>
                                    {uniqueCategories.map((cat) => (
                                        <SelectItem
                                            key={`category-${cat.id}`}
                                            value={cat.id.toString()}
                                        >
                                            {cat.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>
                </div>

                <div className="rounded-lg shadow border">
                    {posts.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center py-12 px-4 rounded-lg">
                            <FileText className="h-12 w-12 text-gray-400 mb-4" />
                            <h3 className="text-lg font-medium mb-1">No posts found</h3>
                            <p className="text-center mb-4">
                                {(search || status !== 'all' || category !== 'all') && (status || category || search)
                                    ? 'No posts found with your filter'
                                    : 'Start by creating a post for your blog'}
                            </p>
                            <Link href={route('admin.posts.create')}>
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Create First Post
                                </Button>
                            </Link>
                        </div>
                    ) : (
                        <Table containerClassName="overflow-visible">
                            <TableHeader className="sticky top-[66px] z-10 bg-background/80 backdrop-blur-md shadow-sm">
                                <TableRow className="hover:bg-transparent">
                                    <TableHead>Title</TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Tags</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Published At</TableHead>
                                    <TableHead>Created At</TableHead>
                                    <TableHead>Action</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {posts.data.map((post) => (
                                    <TableRow key={post.id}>
                                        <TableCell>
                                            <div className="flex items-center space-x-3">
                                                <div className="flex-shrink-0 w-16 h-12 rounded-md overflow-hidden">
                                                    {post.featured_image ? (
                                                        <img
                                                            src={getImageUrl(post)}
                                                            alt={post.title}
                                                            className="w-full h-full object-cover"
                                                            onError={handleImageError}
                                                        />
                                                    ) : (
                                                        <div className="w-full h-full flex items-center justify-center">
                                                            <ImageIcon className="w-6 h-6" />
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="flex flex-col">
                                                    <span className="font-medium">{post.title}</span>
                                                    <span className="text-sm truncate max-w-[300px]">
                                                        {post.excerpt}
                                                    </span>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>{post.category.name}</TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1">
                                                {post.tags.map((tag) => (
                                                    <Badge
                                                        key={tag.id}
                                                        style={{
                                                            backgroundColor: tag.color,
                                                            color: getContrastColor(tag.color),
                                                        }}
                                                    >
                                                        {tag.name}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    post.status === 'published'
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                                className={
                                                    post.status === 'published'
                                                        ? 'bg-green-500 hover:bg-green-600'
                                                        : 'bg-yellow-500 hover:bg-yellow-600'
                                                }
                                            >
                                                {post.status === 'published' ? 'Published' : 'Draft'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {post.published_at
                                                ? format(new Date(post.published_at), 'dd MMMM yyyy', {
                                                      locale: id,
                                                  })
                                                : '-'}
                                        </TableCell>
                                        <TableCell>
                                            {format(new Date(post.created_at), 'dd MMMM yyyy', {
                                                locale: id,
                                            })}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex space-x-2">
                                                <Link href={route('admin.posts.edit', post.id)}>
                                                    <Button variant="outline" size="sm"
                                                        className="hover:bg-[#0c1015] hover:text-white">
                                                        Edit
                                                    </Button>
                                                </Link>
                                                <Link href={route('admin.posts.show', post.id)}>
                                                    <Button variant="outline" size="sm"
                                                        className="hover:bg-[#0c1015] hover:text-white">
                                                        View
                                                    </Button>
                                                </Link>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </div>

                {/* Pagination */}
                {posts.last_page > 1 && (
                    <div className="mt-4 flex justify-center">
                        <div className="flex space-x-2">
                            {Array.from({ length: posts.last_page }, (_, i) => i + 1).map(
                                (page) => {
                                    const queryParams = new URLSearchParams({
                                        page: page.toString(),
                                        search,
                                        status,
                                        category,
                                    }).toString();

                                    return (
                                        <Link
                                            key={`page-${page}`}
                                            href={`${route('admin.posts.index')}?${queryParams}`}
                                            className={`px-3 py-1 rounded ${
                                                page === posts.current_page
                                                    ? 'bg-white text-black'
                                                    : 'bg-gray-800 text-gray-200 hover:bg-[#0c1015]'
                                            }`}
                                        >
                                            {page}
                                        </Link>
                                    );
                                }
                            )}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}

// Helper function to get contrasting text color based on background color
function getContrastColor(hexColor: string): string {
    // Remove the hash if it exists
    const color = hexColor.replace('#', '');

    // Convert hex to RGB
    const r = parseInt(color.substr(0, 2), 16);
    const g = parseInt(color.substr(2, 2), 16);
    const b = parseInt(color.substr(4, 2), 16);

    // Calculate the brightness
    const brightness = (r * 299 + g * 587 + b * 114) / 1000;

    // Return black or white based on brightness
    return brightness > 128 ? '#000000' : '#FFFFFF';
}
