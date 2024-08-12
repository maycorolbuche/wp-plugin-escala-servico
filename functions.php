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
        // Renderiza um item vazio para começar
        echo escsrv_render_item_fields();
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
    $item_dia = isset($item['dia']) ? $item['dia'] : '';
    $item_ordem = isset($item['ordem']) ? $item['ordem'] : 1;

    ob_start();
?>
    <div class="escsrv_item submitbox" style="padding: 5px 0">
        <label for="escsrv_item_nome_<?php echo $index; ?>">Item:</label>
        <input type="text" id="escsrv_item_nome_<?php echo $index; ?>" name="escsrv_itens_da_escala[<?php echo $index; ?>][nome]" value="<?php echo esc_attr($item_nome); ?>" />

        <label for="escsrv_item_dia_<?php echo $index; ?>">Dia:</label>
        <select id="escsrv_item_dia_<?php echo $index; ?>" name="escsrv_itens_da_escala[<?php echo $index; ?>][dia]">
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
    global $post;

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
    if ($a['dia'] == $b['dia']) {
        // Se os dias forem iguais, comparar pelo campo 'ordem'
        return $a['ordem'] <=> $b['ordem'];
    }
    return $a['dia'] <=> $b['dia'];
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

    $itens_da_escala = get_post_meta($post->ID, '_escsrv_itens_da_escala', true);

    if (!empty($itens_da_escala) && is_array($itens_da_escala)) {
        $output = '<ul class="escsrv_itens_da_escala">';

        foreach ($itens_da_escala as $item) {
            $output .= '<li>';
            $output .= '<strong>Item:</strong> ' . esc_html($item['nome']) . '<br>';
            $output .= '<strong>Dia:</strong> ' . esc_html($item['dia']) . '<br>';
            $output .= '<strong>Ordem:</strong> ' . esc_html($item['ordem']);
            $output .= '</li>';
        }

        $output .= '</ul>';
    } else {
        $output = '<p>Nenhum item da escala encontrado.</p>';
    }

    return $output;
}
add_shortcode('escala_itens', 'escsrv_display_itens_da_escala');
