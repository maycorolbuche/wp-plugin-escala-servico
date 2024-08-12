<?php
// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}


function escsrv_disable_autosave()
{
    if (get_post_type() === 'escsrv_escala') {
        wp_deregister_script('autosave');
    }
}
add_action('admin_enqueue_scripts', 'escsrv_disable_autosave');


// Adiciona metaboxes para os campos personalizados
function escsrv_add_custom_meta_box()
{
    add_meta_box(
        'escsrv_meta_box',          // ID
        'Itens da Escala',          // Título
        'escsrv_meta_box_callback', // Callback
        'escsrv_escala',            // Tela onde a metabox aparece
        'normal',                   // Contexto
        'high'                      // Prioridade
    );
}
add_action('add_meta_boxes', 'escsrv_add_custom_meta_box');


function escsrv_admin_styles()
{
    // Verifica se estamos na página de edição do Custom Post Type
    if (get_post_type() === 'escsrv_escala') {
        echo '<style>
            #postdivrich, #postimagediv, #commentstatusdiv, #trackbacksdiv {
                display: none;
            }
        </style>';
    }
}
add_action('admin_head', 'escsrv_admin_styles');


function escsrv_meta_box_callback($post)
{
    wp_nonce_field('escsrv_save_meta_box_data', 'escsrv_meta_box_nonce');

    // Recupera os dados salvos, se existirem
    $itens_da_escala = get_post_meta($post->ID, '_escsrv_itens_da_escala', true);

    echo '<div id="escsrv_itens_wrapper">';

    if (!empty($itens_da_escala) && is_array($itens_da_escala)) {
        foreach ($itens_da_escala as $index => $item) {
            echo escsrv_render_item_fields($item, $index);
        }
    } else {
        // Renderiza itens default pra começar
        echo escsrv_render_item_fields(['nome' => 'Abrir Porta', 'semana' => 7, 'ordem' => 1]);
        echo escsrv_render_item_fields(['nome' => 'Recepção', 'semana' => 7, 'ordem' => 2]);
        echo escsrv_render_item_fields(['nome' => 'Som', 'semana' => 1, 'ordem' => 1]);
        echo escsrv_render_item_fields(['nome' => 'Recepção', 'semana' => 1, 'ordem' => 2]);
        echo escsrv_render_item_fields(['nome' => 'Som', 'semana' => 4, 'ordem' => 1]);
    }

    echo '</div>';

    echo '<button type="button" id="escsrv_add_item_button" class="button button-primary button-large">Adicionar Item</button>';

    // Scripts para adicionar/remover itens dinamicamente
?>
    <script>
        (function() {
            document.getElementById('escsrv_add_item_button').addEventListener('click', function() {
                var newItemHtml = `<?php echo escsrv_render_item_fields([], '[__index__]'); ?>`.replaceAll('[__index__]', generateUUID());
                var wrapper = document.getElementById('escsrv_itens_wrapper');
                wrapper.insertAdjacentHTML('beforeend', newItemHtml);
            });

            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('escsrv_remove_item_button')) {
                    var itemToRemove = event.target.closest('.escsrv_item');
                    if (itemToRemove) {
                        itemToRemove.remove();
                    }
                }
            });

            function generateUUID() {
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                    var r = Math.random() * 16 | 0,
                        v = c === 'x' ? r : (r & 0x3 | 0x8);
                    return v.toString(16);
                });
            }
        })();
    </script>

<?php
}

// Função para renderizar os campos de um item
function escsrv_render_item_fields($item = [], $index = '')
{
    if ($index === '') {
        $index = escsrv_generate_uuid();
    }

    $dias_semana = ['Domingos', 'Segundas', 'Terças', 'Quartas', 'Quintas', 'Sextas', 'Sábados'];
    $item_nome = isset($item['nome']) ? $item['nome'] : '';
    $item_dia = isset($item['semana']) ? $item['semana'] : '';
    $item_ordem = isset($item['ordem']) ? $item['ordem'] : 1;

    ob_start();
?>
    <div class="escsrv_item submitbox" style="padding: 5px 0">
        <label for="escsrv_item_nome_<?php echo $index; ?>">Item:</label>
        <input type="text" id="escsrv_item_nome_<?php echo $index; ?>" name="escsrv_itens_da_escala[<?php echo $index; ?>][nome]" value="<?php echo esc_attr($item_nome); ?>" />

        <label for="escsrv_item_semana_<?php echo $index; ?>">Dia:</label>
        <select id="escsrv_item_semana_<?php echo $index; ?>" name="escsrv_itens_da_escala[<?php echo $index; ?>][semana]">
            <?php foreach ($dias_semana as $dia => $texto) : ?>
                <?php $dia++; ?>
                <option value="<?php echo $dia; ?>" <?php selected($item_dia, $dia); ?>><?php echo $texto; ?></option>
            <?php endforeach; ?>
        </select>

        <label for="escsrv_item_ordem_<?php echo $index; ?>">Ordem:</label>
        <input type="number" id="escsrv_item_ordem_<?php echo $index; ?>" name="escsrv_itens_da_escala[<?php echo $index; ?>][ordem]" value="<?php echo esc_attr($item_ordem); ?>" min="1" />

        <a href="javascript:" type="button" class="escsrv_remove_item_button submitdelete deletion">Remover</a>
    </div>
<?php
    return ob_get_clean();
}

function escsrv_save_meta_box_data($post_id)
{
    if (!isset($_POST['escsrv_meta_box_nonce']) || !wp_verify_nonce($_POST['escsrv_meta_box_nonce'], 'escsrv_save_meta_box_data')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['escsrv_itens_da_escala']) && is_array($_POST['escsrv_itens_da_escala'])) {
        $itens_da_escala = [];
        foreach ($_POST['escsrv_itens_da_escala'] as $key => $item) {
            $key = sanitize_text_field($key);
            $itens_da_escala[$key] = [];
            foreach ($item as $field => $value) {
                $field = sanitize_text_field($field);
                $value = sanitize_text_field($value);
                $itens_da_escala[$key][$field] = $value;
            }
        }
        uasort($itens_da_escala, 'escsrv_sort_items');
        update_post_meta($post_id, '_escsrv_itens_da_escala', $itens_da_escala);
    } else {
        delete_post_meta($post_id, '_escsrv_itens_da_escala');
    }
}
add_action('save_post', 'escsrv_save_meta_box_data');


function escsrv_replace_post_content($post_id, $post, $update)
{
    $mydata = array(
        'ID' => $post_id,
        'post_content' => '[escala_itens]',
    );

    remove_action('save_post', 'escsrv_replace_post_content', 10);
    wp_update_post($mydata);
    add_action('save_post', 'escsrv_replace_post_content', 10, 3);
}
add_action('save_post', 'escsrv_replace_post_content', 10, 3);

function escsrv_sort_items($a, $b)
{
    // Comparar pelo campo 'dia'
    if ($a['semana'] == $b['semana']) {
        // Se os dias forem iguais, comparar pelo campo 'ordem'
        return $a['ordem'] <=> $b['ordem'];
    }
    return $a['semana'] <=> $b['semana'];
}

function escsrv_generate_uuid()
{
    return uniqid();
}

function escsrv_display_itens_da_escala()
{
    global $post;

    if ($post->post_type !== 'escsrv_escala') {
        return '<p>Este shortcode só pode ser usado em posts do tipo "escsrv_escala".</p>';
    }

    $grid = escsrv_monta_grid($post->ID);

    $output = "";
    $output .= "<style>";
    $output .= ".escsrv_escala,.escsrv_escala td{";
    $output .= "border:1px solid #000000;";
    $output .= "border-collapse: collapse;";
    $output .= "padding: 0;";
    $output .= "margin: 0;";
    $output .= "}";
    $output .= ".escsrv_escala .escsrv_escala_semana th{";
    $output .= "background: #000;";
    $output .= "color: #FFF;";
    $output .= "padding: 2px;";
    $output .= "text-transform: uppercase;";
    $output .= "}";
    $output .= ".escsrv_escala .escsrv_escala_dia{";
    $output .= "background: #CCC;";
    $output .= "font-weight: bold;";
    $output .= "text-align: right;";
    $output .= "font-size: 14px;";
    $output .= "padding: 2px 5px;";
    $output .= "}";
    $output .= ".escsrv_escala .escsrv_escala_item{";
    $output .= "background: #CCC;";
    $output .= "font-weight: bold;";
    $output .= "font-size: 14px;";
    $output .= "padding: 2px 5px;";
    $output .= "}";
    $output .= ".escsrv_escala input{";
    $output .= "width: calc(100% - 9px);";
    $output .= "padding: 6px 4px;";
    $output .= "border: 0;";
    $output .= "background: #f7f7c6;";
    $output .= "}";
    $output .= ".escsrv_botao{";
    $output .= "background: #2271b1;";
    $output .= "border-color: #2271b1;";
    $output .= "color: #fff;";
    $output .= "text-decoration: none;";
    $output .= "border: 0;";
    $output .= "padding: 12px 30px;";
    $output .= "cursor: pointer;";
    $output .= "float: right;";
    $output .= "margin-left: 5px;";
    $output .= "}";
    $output .= "</style>";

    $output .= "<div style='font-weight:bold;text-align:center'>" . $grid["titulo"] . "</div>";
    $output .= "<div style='padding: 0;margin: 0;font-size:14px;display: flex;align-items: center;justify-content: space-between;'>";
    foreach ($grid["meses"] as $item) {
        if ($item["dif"] <> 0) {
            $output .= "<a href='?mes=" . $item["mes"] . "&ano=" . $item["ano"] . "'>" . $item["titulo"] . "</a>";
        } elseif ($item["mes"] <> date("m") || $item["ano"] <> date("Y")) {
            $output .= "<a href='?'>Hoje</a>";
        }
    }
    $output .= "</div>";
    $output .= "<form method='post' action=''>";
    $output .= "<input type='hidden' name='escsrv_form_submitted' value='1' />";
    $output .= "<input type='hidden' name='post_id' value='" . $post->ID . "'>";
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
                $output .= "<td>";
                if (isset($dados["dia"])) {
                    $nome = $dados["dados"][$item["id"]] ?? '';
                    $output .= "<input name='escsrv_nome[" . $grid["ano"] . "][" . $grid["mes"] . "][" . $dados["dia"] . "][" . $item["id"] . "]' value='" . $nome . "'>";
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
    $output .= "<input type='submit' value='Salvar' class='escsrv_botao'>";
    $output .= "<button type='button' id='generate_pdf_button' class='escsrv_botao' style='background:#870000;'>Gerar PDF</button>";
    $output .= "</form>";

    $output .= "<script>";
    $output .= "document.getElementById('generate_pdf_button').addEventListener('click', function() {";
    $output .= "    window.open('/wp-json/escsrv/v1/generate_pdf/" . $post->ID . "', '_blank');";
    $output .= "});";
    $output .= "</script>";

    if (isset($_POST['escsrv_form_submitted']) && $_POST['escsrv_form_submitted'] == '1') {
        $output .= "<p>Dados salvos com sucesso!</p>";
    }

    return $output; // Retorna o HTML
}
add_shortcode('escala_itens', 'escsrv_display_itens_da_escala');


function escsrv_process_form_data()
{
    // Verifica se o formulário foi submetido
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['escsrv_nome'])) {
        // Sanitiza e processa os dados do formulário
        $itens = $_POST['escsrv_nome'];

        foreach ($itens as $ano => $aitem) {
            $ano = intval($ano);

            foreach ($aitem as $mes => $mitem) {
                $mes = intval($mes);

                $itens_save = [];
                foreach ($mitem as $dia => $ditem) {
                    $dia = intval($dia);

                    foreach ($ditem as $id => $nome) {
                        $id = sanitize_text_field($id);
                        $nome = sanitize_text_field($nome);

                        $itens_save[$dia][$id] = $nome;
                    }
                }
                ksort($itens_save);
                $post_id = intval($_POST['post_id']);

                update_post_meta($post_id, "_escsrv_item_{$ano}_{$mes}", $itens_save);
            }
        }
    }
}
add_action('init', 'escsrv_process_form_data');


function escsrv_remove_wpautop_from_shortcode($content)
{
    $content = do_shortcode(shortcode_unautop($content));
    return $content;
}
add_filter('the_content', 'escsrv_remove_wpautop_from_shortcode', 0);



//ROTA PARA GERAR PDF
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
    $post_id = $request['id'];
    $post = get_post($post_id);

    if (!$post || $post->post_type !== 'escsrv_escala') {
        return new WP_Error('invalid_post', 'Post inválido.', ['status' => 404]);
    }

    // Gerar o conteúdo HTML que será convertido em PDF
    $grid = escsrv_monta_grid($post->ID);

    $output = "";

    $output .= "<style>";
    $output .= 'body { font-family: Helvetica, sans-serif; }';
    $output .= ".escsrv_escala,.escsrv_escala td{";
    $output .= "width:100%;";
    $output .= "}";
    $output .= ".escsrv_escala,.escsrv_escala td{";
    $output .= "border:1px solid #000000;";
    $output .= "border-collapse: collapse;";
    $output .= "padding: 0;";
    $output .= "margin: 0;";
    $output .= "}";
    $output .= ".escsrv_escala .escsrv_escala_semana th{";
    $output .= "background: #000;";
    $output .= "color: #FFF;";
    $output .= "padding: 10px;";
    $output .= "text-transform: uppercase;";
    $output .= "}";
    $output .= ".escsrv_escala .escsrv_escala_dia{";
    $output .= "background: #CCC;";
    $output .= "font-weight: bold;";
    $output .= "text-align: right;";
    $output .= "font-size: 14px;";
    $output .= "padding: 8px;";
    $output .= "}";
    $output .= ".escsrv_escala .escsrv_escala_item{";
    $output .= "background: #CCC;";
    $output .= "font-weight: bold;";
    $output .= "font-size: 14px;";
    $output .= "padding: 8px;";
    $output .= "}";
    $output .= ".escsrv_escala .escsrv_escala_input{";
    $output .= "font-size: 14px;";
    $output .= "padding: 8px;";
    $output .= "}";
    $output .= ".escsrv_botao{";
    $output .= "background: #2271b1;";
    $output .= "border-color: #2271b1;";
    $output .= "color: #fff;";
    $output .= "text-decoration: none;";
    $output .= "border: 0;";
    $output .= "padding: 12px 30px;";
    $output .= "cursor: pointer;";
    $output .= "float: right;";
    $output .= "margin-left: 5px;";
    $output .= "}";
    $output .= "</style>";

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




function escsrv_monta_grid($post_id)
{
    $mes = intval($_GET["mes"] ?? date("m"));
    $ano = intval($_GET["ano"] ?? date("Y"));;
    $dias = date("t", mktime(0, 0, 0, $mes, '01', $ano));

    $dias_semana = ['Domingos', 'Segundas', 'Terças', 'Quartas', 'Quintas', 'Sextas', 'Sábados'];
    $meses = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];


    $date = DateTime::createFromFormat('Y-m', "$ano-$mes");
    $m = [];
    for ($i = -1; $i <= 1; $i++) {
        $cdate = clone $date;
        $cdate->modify($i . ' month');
        $pmes = $cdate->format('m');
        $pano = $cdate->format('Y');
        $m[] = ["dif" => $i, "mes" => $pmes, "ano" => $pano, "titulo" => $meses[$pmes - 1] . "/" . $pano];
    }


    $grid = [
        "semanas" => 0,
        "dias" => 0,
        "mes" => $mes,
        "ano" => $ano,
        "meses" => $m,
        "titulo" => $meses[$mes - 1] . "/" . $ano,
        "dados" => [],
    ];

    $itens_escala_lista = get_post_meta($post_id, '_escsrv_itens_da_escala', true);
    $nomes_escala = get_post_meta($post_id, '_escsrv_item_' . intval($ano) . '_' . intval($mes), true);

    $itens_escala = [];
    foreach ($itens_escala_lista as $id => $item) {
        $key = $item["semana"];
        if ($key >= 7) {
            $key = 0;
        }
        if (!isset($itens_escala[$key])) {
            $itens_escala[$key] = [];
        }
        $itens_escala[$key][] = [...$item, 'id' => $id];
    }


    $col = 0;
    $data_col = false;
    for ($i = 1; $i <= $dias; $i++) {
        $dia = $i;
        $date = strtotime("$ano-$mes-$dia");

        $semana = date("w", $date);
        $ksemana = ($semana >= 6 ? 0 : $semana + 1);

        $grid["dias"] = $i;

        if (count($itens_escala[$ksemana] ?? []) > 0) {
            $grid["semanas"] = $col + 1;
            $data_col = true;

            if (!isset($grid["dados"][$ksemana])) {
                $grid["dados"][$ksemana] = [
                    "semana" => $dias_semana[$semana],
                    "itens" => $itens_escala[$ksemana] ?? [],
                    "dados" => [],
                ];
            }

            if ($col > 0 && !isset($grid["dados"][$ksemana]["dados"][0])) {
                $grid["dados"][$ksemana]["dados"][0] = [];
            }

            $grid["dados"][$ksemana]["dados"][$col] = [
                "dia" => $dia,
                "dados" => $nomes_escala[$dia] ?? [],
            ];
        }

        if ($ksemana >= 6 && $data_col == true) {
            $col++;
        }
    }

    ksort($grid["dados"]);
    return $grid;
}
