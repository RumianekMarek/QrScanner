import React, { useRef } from "react";
import { QRCodeCanvas } from "qrcode.react";

const QRCode = ({ qrCode }) => {
    const qrRef = useRef();
    
    const qrUrl = window.location.host + '/token/auth/' + qrCode;

    const downloadQR = () => {
        const canvas = qrRef.current.querySelector("canvas");
        const url = canvas.toDataURL("image/png");
        const link = document.createElement("a");
        link.href = url;
        link.download = "qrcode.png";
        link.click();
    };

    return (
        <button onClick={downloadQR} className="ms-2 text-white font-bold py-1 px-4 rounded">
            <div ref={qrRef}>
                <QRCodeCanvas value={qrUrl} size={300} className="max-w-14 max-h-14"/>
            </div>
        </button>
    );
};

export default QRCode;
