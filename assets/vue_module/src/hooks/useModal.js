import { ref, reactive, computed, watch, onMounted } from 'vue';
export default function Modal() {
    /*---Dialog---*/
    const dialogParams = reactive({
        isVisible: false,
        title: '',
        record: {},
        mode: ''
    });

    const onDialogClose = () => {
        dialogParams.record = {};
        dialogParams.title = '';
        dialogParams.isVisible = false;
        dialogParams.mode = '';
    }
    /*---./Dialog---*/
    return {
        dialogParams,
        onDialogClose
    };
}