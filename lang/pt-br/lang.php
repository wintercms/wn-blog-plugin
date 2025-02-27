<?php

return [
    'plugin' => [
        'name' => 'Blog',
        'description' => 'A plataforma de blogs robusta.',
    ],
    'blog' => [
        'menu_label' => 'Blog',
        'menu_description' => 'Gerencie os posts do blog',
        'posts' => 'Posts',
        'create_post' => 'Blog post',
        'categories' => 'Categorias',
        'create_category' => 'Blog categoria',
        'tab' => 'Blog',
        'access_posts' => 'Gerencie os posts do blog',
        'access_categories' => 'Gerenciar as categorias de blog',
        'access_other_posts' => 'Gerencie outros posts de usuários do blog',
        'access_import_export' => 'Permissão para importação e exportação de mensagens',
        'access_publish' => 'Permitido publicar posts',
        'delete_confirm' => 'Você tem certeza?',
        'chart_published' => 'Publicados',
        'chart_drafts' => 'Rascunhos',
        'chart_total' => 'Total',
    ],
    'posts' => [
        'list_title' => 'Gerencie os posts do blog',
        'filter_category' => 'Categoria',
        'filter_published' => 'Esconder publicados',
        'filter_date' => 'Data',
        'new_post' => 'Novo post',
        'export_post' => 'Exportar posts',
        'import_post' => 'Importar posts',
    ],
    'post' => [
        'title' => 'Título',
        'title_placeholder' => 'Novo título do post',
        'content' => 'Conteúdo',
        'content_html' => 'HTML Conteúdo',
        'slug' => 'Slug',
        'slug_placeholder' => 'slug-do-post',
        'categories' => 'Categorias',
        'author_email' => 'Autor Email',
        'created' => 'Criado',
        'created_date' => 'Data de criação',
        'updated' => 'Atualizado',
        'updated_date' => 'Data de atualização',
        'published' => 'Publicado',
        'published_date' => 'Data de publicação',
        'published_validation' => 'Por favor, especifique a data de publicação',
        'tab_edit' => 'Editar',
        'tab_categories' => 'Categorias',
        'categories_comment' => 'Selecione as categorias do blog que o post pertence.',
        'categories_placeholder' => 'Não há categorias, você deve criar um primeiro!',
        'tab_manage' => 'Gerenciar',
        'published_on' => 'Publicado em',
        'excerpt' => 'Resumo',
        'summary' => 'Resumo',
        'featured_images' => 'Imagens destacadas',
        'delete_confirm' => 'Você realmente deseja excluir este post?',
        'close_confirm' => 'O post não foi salvo.',
        'return_to_posts' => 'Voltar à lista de posts',
    ],
    'categories' => [
        'list_title' => 'Gerenciar as categorias do blog',
        'new_category' => 'Nova categoria',
        'uncategorized' => 'Sem categoria',
    ],
    'category' => [
        'name' => 'Nome',
        'name_placeholder' => 'Novo nome para a categoria',
        'description' => 'Descrição',
        'slug' => 'Slug',
        'slug_placeholder' => 'novo-slug-da-categoria',
        'posts' => 'Posts',
        'delete_confirm' => 'Você realmente quer apagar esta categoria?',
        'return_to_categories' => 'Voltar para a lista de categorias do blog',
        'reorder' => 'Reordenar Categorias',
    ],
    'menuitem' => [
        'blog_category' => 'Blog categoria',
        'all_blog_categories' => 'Todas as categorias de blog',
        'blog_post' => 'Blog post',
        'all_blog_posts' => 'Todas as postagens do blog',
    ],
    'settings' => [
        'category_title' => 'Lista de categoria',
        'category_description' => 'Exibe uma lista de categorias de blog na página.',
        'category_slug' => 'Slug da categoria',
        'category_slug_description' => "Olhe para cima, a categoria do blog já está usando o valor fornecido! Esta propriedade é usada pelo componente default parcial para a marcação da categoria atualmente ativa.",
        'category_display_empty' => 'xibir categorias vazias',
        'category_display_empty_description' => 'Mostrar categorias que não tem nenhum post.',
        'category_page' => 'Página da categoria',
        'category_page_description' => 'Nome do arquivo de página da categoria para os links de categoria. Esta propriedade é usada pelo componente default parcial.',
        'post_title' => 'Post',
        'post_description' => 'Exibe um post na página.',
        'post_slug' => 'Post slug',
        'post_slug_description' => "Procure o post do blog usando o valor do slug fornecido.",
        'post_category' => 'Página da categoria',
        'post_category_description' => 'Nome do arquivo de página da categoria para os links de categoria. Esta propriedade é usada pelo componente default parcial.',
        'posts_title' => 'Lista de posts',
        'posts_description' => 'Exibe uma lista de últimas postagens na página.',
        'posts_pagination' => 'Número da pagina',
        'posts_pagination_description' => 'Esse valor é usado para determinar qual página o usuário está.',
        'posts_filter' => 'Filtro de categoria',
        'posts_filter_description' => 'Digite um slug de categoria ou parâmetro de URL para filtrar as mensagens. Deixe em branco para mostrar todas as mensagens.',
        'posts_per_page' => 'Posts por página',
        'posts_per_page_validation' => 'Formato inválido das mensagens por valor de página',
        'posts_no_posts' => 'Nenhuma mensagem de posts',
        'posts_no_posts_description' => 'Mensagem para exibir na lista post no caso, se não há mensagens. Esta propriedade é usada pelo componente default parcial.',
        'posts_order' => 'Orde posts',
        'posts_order_decription' => 'Atributo em que as mensagens devem ser ordenados',
        'posts_category' => 'Página de Categoria',
        'posts_category_description' => 'Nome do arquivo de página da categoria para os links de categoria. Esta propriedade é usada pelo componente default parcial.',
        'posts_post' => 'Página de posts',
        'posts_post_description' => 'Nome do arquivo post página para os "Saiba mais" links. Esta propriedade é usada pelo componente default parcial.',
        'posts_except_post' => 'Except post',
        'posts_except_post_description' => 'Enter ID/URL or variable with post ID/URL you want to except',
        'rssfeed_blog' => 'Página do Blog',
        'rssfeed_blog_description' => 'Nome do arquivo principal da página do blog para geração de links. Essa propriedade é usada pelo componente padrão parcial.',
        'rssfeed_title' => 'RSS Feed',
        'rssfeed_description' => 'Gera um feed RSS que contém posts do blog.',
    ],
];
