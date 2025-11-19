import React, { useState } from 'react';
import { router } from '@inertiajs/react';

export default function ButtonAction({
  endpoint,
  method = 'post',
  data = {},
  label = 'Kliknij',
  successLabel = 'Zrobione',
  color = 'blue',
}) {
  const [loading, setLoading] = useState(false);
  const [done, setDone] = useState(false);

  const handleClick = () => {
    if (loading || done) return;
    setLoading(true);

    router.visit(endpoint, {
      method,
      data,
      preserveScroll: true,
    });
  };

  // Style przycisku — dopasowane do koloru
  const baseClasses =
    'flex items-center justify-center font-semibold rounded-lg transition-colors duration-150 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-70';
  const colorClasses = {
    blue: 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
    red: 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
    green: 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
    gray: 'bg-gray-500 hover:bg-gray-600 text-white focus:ring-gray-400',
  };

  return (
    <button
      onClick={handleClick}
      disabled={loading || done}
      className={`${baseClasses} ${colorClasses[color]}`}
    >
      {loading ? (
        <span className="flex items-center">
          <svg
            className="animate-spin h-5 w-5 mr-2 text-white"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle
              className="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              strokeWidth="4"
            ></circle>
            <path
              className="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"
            ></path>
          </svg>
          Przetwarzanie...
        </span>
      ) : done ? (
        successLabel
      ) : (
        label
      )}
    </button>
  );
}
