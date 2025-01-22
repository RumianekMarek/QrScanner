import { useEffect } from 'react';
export default function useEvent(event, eventListener, passive) {
    if (passive === void 0) { passive = false; }
    useEffect(function () {
        document.addEventListener(event, eventListener, passive);
        return function cleanup() {
            document.removeEventListener(event, eventListener);
        };
    });
}
