<!doctype html>

<html lang="es">

<head>
    <meta charset="utf-8">

```
<style>
    @page {
        margin: 35px 38px 45px 38px;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 10px;
        color: #26384e;
        margin: 0;
        padding: 0;
    }

    .header {
        width: 100%;
        border-bottom: 3px solid #174a88;
        padding-bottom: 14px;
        margin-bottom: 20px;
    }

    .institution {
        color: #10345f;
        font-size: 18px;
        font-weight: bold;
        margin: 0 0 5px;
    }

    .subtitle {
        color: #718096;
        font-size: 9px;
        margin: 0;
    }

    .report-title {
        background: #eaf2ff;
        border-left: 5px solid #174a88;
        padding: 10px 12px;
        margin-bottom: 18px;
    }

    .report-title h1 {
        color: #10345f;
        font-size: 15px;
        margin: 0;
        font-weight: bold;
    }

    .metadata {
        width: 100%;
        margin-bottom: 20px;
    }

    .metadata td {
        border: none;
        padding: 4px 8px 4px 0;
        vertical-align: top;
    }

    .metadata-label {
        color: #718096;
        font-size: 8px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .metadata-value {
        color: #26384e;
        font-size: 10px;
    }

    .section-title {
        color: #10345f;
        font-size: 11px;
        font-weight: bold;
        border-bottom: 1px solid #dbe5f1;
        padding-bottom: 6px;
        margin-bottom: 10px;
    }

    table.data-table {
        border-collapse: collapse;
        width: 100%;
        table-layout: auto;
    }

    .data-table th {
        background: #174a88;
        color: #ffffff;
        border: 1px solid #10345f;
        padding: 7px 6px;
        font-size: 8px;
        font-weight: bold;
        text-align: left;
    }

    .data-table td {
        border: 1px solid #dbe5f1;
        padding: 6px;
        font-size: 8.5px;
        vertical-align: top;
        word-wrap: break-word;
    }

    .data-table tr:nth-child(even) td {
        background: #f7faff;
    }

    .empty {
        border: 1px solid #dbe5f1;
        background: #f8fbff;
        padding: 15px;
        text-align: center;
        color: #718096;
        font-size: 9px;
    }

    .footer {
        position: fixed;
        bottom: -25px;
        left: 0;
        right: 0;
        border-top: 1px solid #dbe5f1;
        padding-top: 7px;
        color: #718096;
        font-size: 7.5px;
        text-align: center;
    }

    .confidential {
        color: #10345f;
        font-weight: bold;
    }
</style>
```

</head>

<body>


{{-- Encabezado institucional --}}
<div class="header">
    <p class="institution">
        Escuela Modelo
    </p>

    <p class="subtitle">
        Sistema Institucional de Tutorías
    </p>
</div>


{{-- Título del reporte --}}
<div class="report-title">
    <h1>
        Reporte de {{ $tipo }}
    </h1>
</div>


{{-- Información del reporte --}}
<table class="metadata">
    <tr>
        <td width="18%">
            <div class="metadata-label">
                Motivo
            </div>

            <div class="metadata-value">
                {{ $motivo }}
            </div>
        </td>

        <td width="18%">
            <div class="metadata-label">
                Fecha de generación
            </div>

            <div class="metadata-value">
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </td>

        <td width="18%">
            <div class="metadata-label">
                Registros
            </div>

            <div class="metadata-value">
                {{ $rows->count() }}
            </div>
        </td>
    </tr>
</table>


{{-- Información --}}
<div class="section-title">
    Información del reporte
</div>


{{-- Tabla de resultados --}}
@if($rows->isNotEmpty())

    <table class="data-table">

        <thead>
            <tr>
                @foreach(array_keys((array) $rows->first()) as $name)
                    <th>
                        {{ $name }}
                    </th>
                @endforeach
            </tr>
        </thead>

        <tbody>

            @foreach($rows as $row)

                <tr>

                    @foreach((array) $row as $value)

                        <td>
                            {{ $value }}
                        </td>

                    @endforeach

                </tr>

            @endforeach

        </tbody>

    </table>

@else

    <div class="empty">
        No se encontraron registros para los criterios seleccionados.
    </div>

@endif


{{-- Pie de página --}}
<div class="footer">
    <span class="confidential">
        Documento institucional
    </span>
    &nbsp; | &nbsp;
    Generado mediante el Sistema Institucional de Tutorías
    &nbsp; | &nbsp;
    Información de uso administrativo
</div>


</body>

</html>
