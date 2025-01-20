import { forwardRef, useEffect, useImperativeHandle, useRef } from 'react';

export default forwardRef(function Select(
    { 
        type = 'select', 
        className = '',
        label = '', 
        isFocused = false, 
        options =[], 
        valueKey = 'id', 
        labelKey = 'name', 
        ...props 
    },
    ref,
) {
    const localRef = useRef(null);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    return (
        <select
            {...props}
            type={type}
            className={
                'block w-full mt-1 rounded-md border-gray-300 shadow-sm' +
                className
            }
            ref={localRef}
            >
            <option value="">{label}</option>

            {options.map((option, index) => (
                <option key={index} value={option[valueKey]}>
                {option[labelKey]}
            </option>
            ))}
        </select>
    );
});
