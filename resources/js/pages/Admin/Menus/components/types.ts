export interface MenuItemData {
    id: string | number;
    title: string;
    url: string;
    type: 'custom' | 'page' | 'post' | 'home';
    target?: '_self' | '_blank';
    order: number;
    parent_id?: number | null;
    children: MenuItemData[];
}

export interface Menu {
    id: string | number;
    name: string;
    location: string;
    description?: string;
    is_active: boolean;
    items: MenuItemData[];
}
