import { useState, useRef, useEffect } from 'react';

export default function VoiceInputButton({ onChange }) {
    const [isRecording, setIsRecording] = useState(false);
    const textRef = useRef('');
    const speakerRef = useRef(null);

    useEffect(() => {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            alert("SpeechRecognition is not supported");
            return;
        }

        const speaker = new SpeechRecognition();
        speaker.continuous = true;
        speaker.lang = 'pl-PL';

                
        speaker.onresult = (event) => {
            const newText = Array.from(event.results).map((chunk) => {return chunk[0].transcript}).join(' ');
            textRef.current += (textRef.current ? " " : "") + newText;
            onChange(textRef.current);
        }

        speakerRef.current = speaker;
    }, []);
    
    const toggleListening = () => {
        if (!speakerRef.current) return;

        const speaker = speakerRef.current;

        if(isRecording){
            speaker.stop();
            setIsRecording(false);

            return;
        } else {
            speaker.start();
            setIsRecording(true);
        }
    };

    return (
        <button
            type="button" 
            onClick={toggleListening} 
            className={isRecording ? 'bg-red-500' : 'bg-green-500'}
        >
            {isRecording ? 'Nagrywanie... ⏹ Zatrzymaj' : '🎤 Dodaj notatkę głosową'}
        </button>
    );
}