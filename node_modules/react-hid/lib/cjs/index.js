"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.useHidWithActionKey = exports.useHid = void 0;
var react_1 = require("react");
var useEvent_1 = __importDefault(require("./useEvent"));
function useHid(handler) {
    var handleKeyReceived = function (keyboardEvent) {
        handler(keyboardEvent.key);
    };
    return (0, useEvent_1.default)('keydown', handleKeyReceived);
}
exports.useHid = useHid;
function useHidWithActionKey(key, handler) {
    var _a = (0, react_1.useState)(''), tempKeys = _a[0], setTempKeys = _a[1];
    var handleKeyReceived = function (keyboardEvent) {
        if (keyboardEvent.key === key) {
            handler(tempKeys);
            setTempKeys("");
        }
        else {
            setTempKeys("".concat(tempKeys).concat(keyboardEvent.key));
        }
    };
    return (0, useEvent_1.default)('keydown', handleKeyReceived);
}
exports.useHidWithActionKey = useHidWithActionKey;
