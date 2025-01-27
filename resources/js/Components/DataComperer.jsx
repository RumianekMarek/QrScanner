import React from 'react';

export default function DataComperer({ firstDate, secondDate, onResult = null }) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const parseDate = (dateString) => new Date(dateString.replace(/\//g, '-'));

    if (!firstDate || !secondDate || isNaN(parseDate(firstDate)) || isNaN(parseDate(secondDate))) {
        return <td className="border px-4 py-2 text-center text-gray-500">Nie przypisano daty</td>;
    }

    const isFirstDateAfterToday = parseDate(firstDate) < today;
    const isSecondDateBeforeToday = parseDate(secondDate) >= today;

    React.useEffect(() => {
        if (onResult) {
            onResult({
                isFirstDateAfterToday,
                isSecondDateBeforeToday,
            });
        }
    }, [firstDate, secondDate]);

    return (
        (isFirstDateAfterToday && isSecondDateBeforeToday) ?
            <td className="border px-4 py-2 text-center text-green-500">Aktywne</td> 
            : 
            <td className="border px-4 py-2 text-center text-red-500">Nie Aktywne</td>
    );
}
