import { ref, reactive, computed, watch, onMounted } from 'vue';
export default function Modal() {
    /*---Dialog---*/
    const dialogParams = reactive({
        isVisible: false,
        title: '',
        record: {}
    });

    const onDialogClose = () => {
        dialogParams.record = {};
        dialogParams.title = '';
        dialogParams.isVisible = false;
    }
    /*---./Dialog---*/
    return {
        dialogParams,
        onDialogClose
    };
}