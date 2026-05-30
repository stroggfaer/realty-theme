
<?php
/**
 * Template Name: Пример
 */
get_header();
?>
<div class="page-container">
    <main id="site-content-home" role="main">
        <!-- Vue 3 Hello World Test -->
        <div id="vue-app">
            <hello-word></hello-word>
            <el-button @click="visible = true">Button</el-button>
            <el-dialog v-model="visible" title="Shipping address" width="500">
                <p>Try Element</p>
            </el-dialog>
        </div>
        <script type="module">
            const {HelloWorld, createAppModule, ElButton, ElDialog } = window.VueAppModule;
            const { ref } = Vue;
            const AppTestWp = createAppModule({
                setup() {
                    const visible = ref(false);
                    return {
                        visible
                    }
                }
            });
            AppTestWp.component('el-button', ElButton);
            AppTestWp.component('el-dialog', ElDialog);
            AppTestWp.component('hello-word', HelloWorld);
            AppTestWp.mount('#vue-app');
        </script>

     
    </main><!-- #site-content -->
    <?php get_footer(); ?>
</div>


