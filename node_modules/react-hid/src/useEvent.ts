import { useEffect } from 'react';

export default function useEvent(
  event: keyof DocumentEventMap,
  eventListener: EventListener,
  passive = false,
): void {
  useEffect(() => {
    document.addEventListener(event, eventListener, passive);
    return function cleanup() {
      document.removeEventListener(event, eventListener);
    };
  });
}
