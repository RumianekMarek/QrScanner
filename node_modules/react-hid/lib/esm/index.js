import { useState } from 'react';
import useEvent from './useEvent';
export function useHid(handler) {
    var handleKeyReceived = function (keyboardEvent) {
        handler(keyboardEvent.key);
    };
    return useEvent('keydown', handleKeyReceived);
}
export function useHidWithActionKey(key, handler) {
    var _a = useState(''), tempKeys = _a[0], setTempKeys = _a[1];
    var handleKeyReceived = function (keyboardEvent) {
        if (keyboardEvent.key === key) {
            handler(tempKeys);
            setTempKeys("");
        }
        else {
            setTempKeys("".concat(tempKeys).concat(keyboardEvent.key));
        }
    };
    return useEvent('keydown', handleKeyReceived);
}
