import store from './src/store/index.js';
import HelloWorld from './src/components/HelloWord.js';
import PropertyFilter from './src/components/PropertyFilter.js';
import HomePropertyFilter from './src/components/HomePropertyFilter.js';
import PropertyBookingSidebar from './src/components/PropertyBookingSidebar.js';
import AppContentMap from './src/components/AppContentMap.js';

import AuthLogin from './src/components/my/AuthLogin.js';
import AuthRegistration from './src/components/my/AuthRegistration.js';
import AuthForgot from './src/components/my/AuthForgot.js';
import MessageToHost from './src/components/my/MessageToHost.js';
import HostProfileModal from './src/components/my/HostProfileModal.js';
import Settings from './src/components/my/Settings.js';


import useProperty from './src/hooks/useProperty.js';
import usePropertyFilters from './src/hooks/usePropertyFilters';
import useModal from './src/hooks/useModal.js';
import { useGoogleMap } from './src/hooks/useGoogleMap.js';
import GoogleMap from './src/components/GoogleMap.js';
import ElementPlus from 'element-plus'
// Локализация для русского языка
import ru from 'element-plus/es/locale/lang/ru';
import moment from 'moment';
import 'moment/locale/ru';
moment.locale('ru');
import dayjs from 'dayjs'
import 'dayjs/locale/ru'

dayjs.locale('ru')
import {
  ElConfigProvider,
  ElButton,
  ElDialog,
  ElForm,
  ElFormItem,
  ElInput,
  ElInputNumber,
  ElDatePicker,
  ElAutocomplete,
  ElMessage,
  ElPopover,
  ElCarousel,
  ElCarouselItem,
  ElAlert,
  ElNotification,
} from 'element-plus';
import {ApiService} from './src/services/api';
export function createAppModule(options = {}) {
  const app = Vue.createApp(options);
  app.config.compilerOptions =  {
     isCustomElement: (tag) => tag === 'noindex',
  };
  
  // Настройка русской локализации для ElementPlus
  app.use(ElementPlus, {
    locale: ru,
  })

  // Глобальная регистрация moment
  app.provide('moment', moment);

  app.use(store);
  return app;
}
export {
  HelloWorld,
  PropertyFilter,
  HomePropertyFilter,
  PropertyBookingSidebar,
  AppContentMap,
  AuthLogin,
  AuthRegistration,
  AuthForgot,
  MessageToHost,
  HostProfileModal,
  Settings,
  GoogleMap,
  ElButton,
  ElDialog,
  ElForm,
  ElFormItem,
  ElInput,
  ElInputNumber,
  ElDatePicker,
  ElAutocomplete,
  ElPopover,
  ElMessage,
  ElAlert,
  ElNotification,
  ApiService,
  useProperty,
  usePropertyFilters,
  useModal,
  useGoogleMap
}
