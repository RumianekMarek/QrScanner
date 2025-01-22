import { useState } from 'react';
import useEvent from './useEvent';
import { Handler } from './types';

export function useHid(handler: Handler): void {
  const handleKeyReceived = (keyboardEvent: KeyboardEvent) => {
    handler(keyboardEvent.key);
  };
  return useEvent('keydown', handleKeyReceived);
}

export function useHidWithActionKey(key: string, handler: Handler): void {
  const [tempKeys, setTempKeys] = useState('');
  const handleKeyReceived = (keyboardEvent: KeyboardEvent) => {
    if (keyboardEvent.key === key) {
      handler(tempKeys);
      setTempKeys("")
    } else {
      setTempKeys(`${tempKeys}${keyboardEvent.key}`);
    }
  };
  return useEvent('keydown', handleKeyReceived);
}
