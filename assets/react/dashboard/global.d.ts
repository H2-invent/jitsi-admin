interface MdbWidget {
    dispose(): void;
}

interface MdbWidgetConstructor {
    getOrCreateInstance(element: Element): MdbWidget;
    getInstance(element: Element): MdbWidget | null;
}

interface MdbModal extends MdbWidget {
    show(): void;
    hide(): void;
}

interface MdbModalConstructor {
    getOrCreateInstance(element: Element): MdbModal;
    getInstance(element: Element): MdbModal | null;
}

declare global {
    interface Window {
        mdb?: {
            Modal: MdbModalConstructor;
            Dropdown: MdbWidgetConstructor;
            Popover: MdbWidgetConstructor;
            Tooltip: MdbWidgetConstructor;
        };
    }
}

export {};
