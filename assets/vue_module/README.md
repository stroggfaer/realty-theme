Пример использвать в php template wp-content/themes/realty-theme/templates/template-ui-kit.php

````
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
````
Пример использвать в vue.js: в папкеwp-content/themes/realty-theme/assets/vue_module/src
```
import { ref } from 'vue';

export default {
  // language=Vue
  template: `
    <div class="hello-word">
      <h1>{{ message }}</h1>
    </div>
  `,

  setup() {
    const message = ref('Hello, World!');
    return {
      message
    };
  }
};

```
Правило для внутри модуля модуля:
- не создаем расширения .vue
- Создаем расширентя .js для vue модули
- Используется подход CDN
- Для компонента создаем HelloWord.js

Правило для php/html:
- В php temp данные отправляеи json формат для vue
- json экранируем данные кавычки и т.д.