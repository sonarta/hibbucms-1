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

/** Partial update payload for menu items (form fields + optional nested children from drag-and-drop). */
export type MenuItemUpdatePayload = Partial<
    Pick<MenuItemData, 'title' | 'url' | 'target' | 'type' | 'order' | 'parent_id'>
> & {
    children?: MenuItemData[];
};

/** Sent to reorder endpoint after drag-and-drop. */
export interface MenuReorderPayload {
    id: string | number;
    order: number;
    parent_id?: string | number | null;
}
