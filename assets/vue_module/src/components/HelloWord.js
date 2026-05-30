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
