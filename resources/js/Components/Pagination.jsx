export default function Pagination({ currentPage, totalPages, totalEntries,  onPageChange, onPageLengthChange }) {
    if(totalEntries < 200) return;

    const pagesToShowStart = currentPage > 5 ? currentPage - 4 : 1;

    const pages = [];
    for(let i = 0; i < 9 && i < totalPages; i++){
        pages.push( pagesToShowStart + i);
    }

    return (
        <div className="pagging__container">
            <button 
                onClick={() => onPageChange(currentPage - 1)}
                disabled={currentPage == 1}
                className="mx-3 my-2 px-3 py-1 rounded bg-gray-200 disabled:opacity-50"
            >
                Poprzednia
            </button>

            {pages.map((page) => (
                <button 
                    key={page}
                    onClick={() => onPageChange(page)}
                    className={`px-3 py-1 rounded ${
                        currentPage === page ? 'bg-blue-500 text-white text-[16px]' : 'bg-gray-200 text-[12px]'
                    }`}
                    
                >
                    {page}
                </button>
                ))
            }

            <button 
                className="mx-3 my-2 px-3 py-1 rounded bg-gray-200 disabled:opacity-50"
                onClick={() => onPageChange(currentPage + 1)}
                disabled={currentPage == totalPages}
            >
                Następna
            </button>

            <select
                onChange={(e) => onPageLengthChange(e.target.value)}
                className="mx-3 my-2 px-3 py-1 pr-6 min-w-32 rounded bg-gray-200 text-black border border-gray-300 cursor-pointer appearance-none"
                >
                <option value="200" className="mx-3">200</option>
                <option value="500" className="mx-3">500</option>
                { totalEntries > 500 && (
                    <option value="1000" className="mx-3">1000</option>
                    )
                }
            </select>

        </div>
    )
}