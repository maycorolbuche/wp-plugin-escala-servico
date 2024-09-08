<?php
// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}

function escsrv_register_rest_routes()
{
    register_rest_route('escsrv/v1', '/generate_pdf/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'escsrv_generate_pdf',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'escsrv_register_rest_routes');

function escsrv_generate_pdf($request)
{
    $post_id = absint(sanitize_text_field($request['id']));
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'escsrv_escala') {
        return new WP_Error('invalid_post', 'Post inválido.', ['status' => 404]);
    }

    // Gerar o conteúdo HTML que será convertido em PDF
    $grid = escsrv_monta_grid($post->ID);

    $output = "";

    $css = <<<CSS
        body { font-family: Helvetica, sans-serif; }
        .escsrv_escala,.escsrv_escala td{
            width:100%;
        }
        .escsrv_escala,.escsrv_escala td{
            border:1px solid #000000;
            border-collapse: collapse;
            padding: 0;
            margin: 0;
        }
        .escsrv_escala .escsrv_escala_semana th{
            background: #000;
            color: #FFF;
            padding: 10px;
            text-transform: uppercase;
        }
        .escsrv_escala .escsrv_escala_dia{
        background: #CCC;
        font-weight: bold;
        text-align: right;
        font-size: 14px;
        padding: 8px;
        }
        .escsrv_escala .escsrv_escala_item{
            background: #CCC;
            font-weight: bold;
            font-size: 14px;
            padding: 8px;
        }
        .escsrv_escala .escsrv_escala_input{
            font-size: 14px;
            padding: 8px;
        }
        .escsrv_botao{
            background: #2271b1;
            border-color: #2271b1;
            color: #fff;
            text-decoration: none;
            border: 0;
            padding: 12px 30px;
            cursor: pointer;
            float: right;
            margin-left: 5px;
        }
    CSS;

    $output .= "<style>$css</style>";

    $output .= "<div style='text-align:center'>ESCALA DE SERVIÇO</div>";
    $output .= "<div style='text-align:center;font-weight:bold;text-transform: uppercase;font-size:26px;'>" . get_the_title($post->ID) . "</div>";
    $output .= "<div style='font-weight:bold;text-align:right'>" . $grid["titulo"] . "</div>";
    $output .= "<hr>";

    $output .= "<table class='escsrv_escala'>";
    foreach ($grid["dados"] as $semana) {
        // DIAS DA SEMANA
        $output .= "<tr class='escsrv_escala_semana'>";
        $output .= "<th colspan='" . ($grid["semanas"] + 1) . "'>";
        $output .= $semana["semana"];
        $output .= "</th>";
        $output .= "</tr>";

        // DIAS DO MES
        $output .= "<tr>";
        $output .= "<td class='escsrv_escala_dia'>";
        $output .= "</td>";
        foreach ($semana["dados"] as $item) {
            $output .= "<td class='escsrv_escala_dia'>";
            $output .= ($item["dia"] ?? '');
            $output .= "</td>";
        }
        // COMPLETAR COM CÉLULAS VAZIAS
        for ($i = count($semana["dados"]); $i <= $grid["semanas"] - 1; $i++) {
            $output .= "<td class='escsrv_escala_dia'>&nbsp;</td>";
        }
        $output .= "</tr>";

        // CAMPOS
        foreach ($semana["itens"] as $item) {
            $output .= "<tr>";
            $output .= "<td class='escsrv_escala_item'>";
            $output .= $item["nome"];
            $output .= "</td>";
            foreach ($semana["dados"] as $dados) {
                $output .= "<td class='escsrv_escala_input'>";
                if (isset($dados["dia"])) {
                    $nome = $dados["dados"][$item["id"]] ?? '';
                    $output .= $nome;
                }
                $output .= "</td>";
            }
            // COMPLETAR COM CÉLULAS VAZIAS
            for ($i = count($semana["dados"]); $i <= $grid["semanas"] - 1; $i++) {
                $output .= "<td>&nbsp;</td>";
            }
            $output .= "</tr>";
        }
    }
    $output .= "</table>";

    // Usando DOMPDF para gerar o PDF
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($output);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    // Forçar download do PDF
    $dompdf->stream("escala_itens.pdf", ["Attachment" => 0]);

    exit;
}
