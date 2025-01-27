import { Html5QrcodeScanner } from "html5-qrcode";
import { useEffect, useState } from "react";
import { usePage } from "@inertiajs/react";

export default function CameraScan({ onScan }) {
    useEffect(() => {
        const qrCameraScan = new Html5QrcodeScanner("reader", {
            fps: 0.5,
            qrbox: { width: 250, height: 250 },
            videoConstraints: { facingMode: "environment" },
        });

        qrCameraScan.render(
            (decodedText) => {
                if (onScan) onScan(decodedText);
            },
        );
        
        return () => {
            qrCameraScan.clear();
        };
    }, []);

    return <div id="reader"></div>;
}
