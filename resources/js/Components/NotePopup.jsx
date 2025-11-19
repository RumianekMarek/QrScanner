import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { useForm } from '@inertiajs/react';

export default function NotePopup({ noteDetails = '', qrCode = 'unknown',user_id, onClose, target_route }) {

    const { data, setData, post, processing, errors} = useForm({
        user_id: user_id,
        qr_code: qrCode,
        note: noteDetails ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route(target_route), {
            onFinish: () => {onClose();}
        });
    };

    return (
        <div className="fixed inset-0 bg-gray-800 bg-opacity-75 flex items-center justify-center">
            <div className="bg-white p-6 rounded shadow-lg w-1/2">
                <h2 className="text-xl font-bold">Edit User Details</h2>
                {/* Sprawdzenie, czy qr_code istnieje */}
                {!data.qr_code ? (
                    <><div className="mt-5 text-red-600">
                        Nie można utworzyć notatki – kod Qr nie istnieje lub jest niepoprawny.
                    </div>
                    <div className="mt-4 ml-4 flex justify-end">
                        <PrimaryButton 
                            className="ml-4 bg-red-500 hover:bg-red-800"
                            onClick={onClose}
                        >
                            Anuluj
                        </PrimaryButton>
                    </div></>
                ) : (
                    <form onSubmit={submit}>
                        <div className="mt-5">
                            <InputLabel htmlFor="note" value="Notatka" />

                            <textarea
                                id="note"
                                name="note"
                                value={data.note}
                                className="mt-1 block w-full border rounded px-3 py-2"
                                rows={5}
                                onChange={(e) => setData('note', e.target.value)}
                                required
                                autoFocus
                            />

                            <InputError message={errors.note} className="mt-2" />
                        </div>
                        <div className="mt-4 flex justify-end">
                            <PrimaryButton className="ml-4 bg-green-500 hover:bg-green-800" disabled={processing}>
                                Zapisz
                            </PrimaryButton>
                            <PrimaryButton className="ml-4 bg-red-500 hover:bg-red-800" onClick={onClose}>
                                Anuluj
                            </PrimaryButton>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}
