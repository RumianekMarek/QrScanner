# React HID

React hooks for capturing entries from HID devices.<br/>

## Install

    yarn add react-hid

or <br />

    npm install --save react-hid

## Usage

Retrieve all key strokes before an action key is pressed<br/>

    import { useHidWithActionKey } from 'react-hid';

    const handleKeys = (keys: string) => {
      console.log(keys);
    };

    useHidWithActionKey('Enter', handleKeys);

Retrieve all key strokes real time<br/>

    import { useHid } from 'react-hid';

    const handleKey = (key: string) => {
      console.log(key);
    };

    useHid(handleKey);
