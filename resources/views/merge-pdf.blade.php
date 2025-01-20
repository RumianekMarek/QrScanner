<form action="{{ route('pdf.mergePdf') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <button type="submit">Połącz PDF</button>
</form>