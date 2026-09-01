import { Check, ChevronDown } from 'lucide-react';
import { Children, isValidElement, ReactNode, useEffect, useId, useLayoutEffect, useRef, useState } from 'react';
import { createPortal } from 'react-dom';

type DropdownOption = {
    disabled: boolean;
    label: ReactNode;
    value: string;
};

type SelectChangeEvent = {
    target: { value: string };
};

type Props = {
    'aria-label'?: string;
    children: ReactNode;
    className?: string;
    defaultValue?: string | number;
    disabled?: boolean;
    name?: string;
    onChange?: (event: SelectChangeEvent) => void;
    required?: boolean;
    value?: string | number;
};

export default function SelectDropdown({ 'aria-label': ariaLabel, children, className = '', defaultValue, disabled = false, name, onChange, required = false, value }: Props) {
    const options = Children.toArray(children).flatMap((child): DropdownOption[] => {
        if (! isValidElement<{ children?: ReactNode; disabled?: boolean; value?: string | number }>(child) || child.type !== 'option') {
            return [];
        }

        return [{
            disabled: Boolean(child.props.disabled),
            label: child.props.children,
            value: String(child.props.value ?? child.props.children ?? ''),
        }];
    });
    const isControlled = value !== undefined;
    const fallbackValue = String(defaultValue ?? options[0]?.value ?? '');
    const [internalValue, setInternalValue] = useState(fallbackValue);
    const [open, setOpen] = useState(false);
    const [menuPosition, setMenuPosition] = useState({ left: 0, top: 0, width: 0 });
    const triggerRef = useRef<HTMLButtonElement>(null);
    const menuRef = useRef<HTMLDivElement>(null);
    const listboxId = useId();
    const selectedValue = isControlled ? String(value) : internalValue;
    const selectedOption = options.find(option => option.value === selectedValue) ?? options[0];

    useLayoutEffect(() => {
        if (! open || ! triggerRef.current) {
            return;
        }

        const bounds = triggerRef.current.getBoundingClientRect();
        const estimatedHeight = Math.min(options.length * 42 + 12, 248);
        const opensUpward = window.innerHeight - bounds.bottom < estimatedHeight && bounds.top > estimatedHeight;
        const width = Math.max(bounds.width, 190);

        setMenuPosition({
            left: Math.max(8, Math.min(bounds.left, window.innerWidth - width - 8)),
            top: opensUpward ? Math.max(8, bounds.top - estimatedHeight - 6) : bounds.bottom + 6,
            width,
        });
    }, [open, options.length]);

    useEffect(() => {
        if (! open) {
            return;
        }

        const closeOnOutsideClick = (event: MouseEvent) => {
            const target = event.target as Node;

            if (! triggerRef.current?.contains(target) && ! menuRef.current?.contains(target)) {
                setOpen(false);
            }
        };
        const closeOnEscape = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                setOpen(false);
                triggerRef.current?.focus();
            }
        };

        document.addEventListener('mousedown', closeOnOutsideClick);
        document.addEventListener('keydown', closeOnEscape);
        window.addEventListener('resize', closeMenu);

        return () => {
            document.removeEventListener('mousedown', closeOnOutsideClick);
            document.removeEventListener('keydown', closeOnEscape);
            window.removeEventListener('resize', closeMenu);
        };
    }, [open]);

    const choose = (option: DropdownOption) => {
        if (option.disabled) {
            return;
        }

        if (! isControlled) {
            setInternalValue(option.value);
        }

        onChange?.({ target: { value: option.value } });
        setOpen(false);
        triggerRef.current?.focus();
    };

    function closeMenu(): void {
        setOpen(false);
    }

    return <div className={`select-dropdown ${className}`}>
        {name && <input type="hidden" name={name} value={selectedValue} disabled={disabled} required={required} />}
        <button ref={triggerRef} type="button" className="select-dropdown-trigger" aria-label={ariaLabel} aria-haspopup="listbox" aria-expanded={open} aria-controls={listboxId} disabled={disabled} onClick={() => setOpen(current => ! current)}>
            <span>{selectedOption?.label ?? 'Select an option'}</span>
            <ChevronDown className={open ? 'open' : ''} />
        </button>
        {open && createPortal(<div ref={menuRef} id={listboxId} role="listbox" className="select-dropdown-menu" style={menuPosition}>
            {options.map(option => <button key={`${option.value}-${String(option.label)}`} type="button" role="option" aria-selected={option.value === selectedValue} disabled={option.disabled} className={option.value === selectedValue ? 'selected' : ''} onClick={() => choose(option)}>
                <span>{option.label}</span>{option.value === selectedValue && <Check />}
            </button>)}
        </div>, document.body)}
    </div>;
}
