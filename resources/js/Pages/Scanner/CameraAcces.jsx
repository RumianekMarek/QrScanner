import { useEffect, useRef } from "react";
import CameraScan from './CameraScan';

export default function CameraAccess({ scanned }) {
    const videoRef = useRef(null);

    const handleScan = (decodedText) => {
        scanned(decodedText);
    };

    useEffect(() => {
        return () => {
            if (videoRef.current && videoRef.current.srcObject) {
                const stream = videoRef.current.srcObject;
                const tracks = stream.getTracks();

                tracks.forEach((track) => track.stop());
            }
        };
    }, []);

    return (
        <>  
            <div className="w-4/5 mt-10 m-auto h-auto max-w-lg">
                <CameraScan onScan={handleScan} />
            </div>
        </>
    )
}
