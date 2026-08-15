import { defineConfig } from 'vitepress'

const enSidebar = {
  '/dynamic-table/': [
    {
      text: 'Dynamic Table Engine',
      items: [
        { text: 'Overview', link: '/dynamic-table/README' },
        { text: 'Quick Start', link: '/dynamic-table/quick-start' },
        { text: 'Columns', link: '/dynamic-table/columns' },
        { text: 'Visibility & Authorization', link: '/dynamic-table/visibility-authorization' },
        { text: 'Search', link: '/dynamic-table/search' },
        { text: 'Filters', link: '/dynamic-table/filters' },
        { text: 'Relationships', link: '/dynamic-table/relationships' },
        { text: 'Sorting & Pagination', link: '/dynamic-table/sorting-pagination' },
        { text: 'Preferences', link: '/dynamic-table/preferences' },
        { text: 'Saved Views', link: '/dynamic-table/saved-views' },
        { text: 'Performance', link: '/dynamic-table/performance' },
        { text: 'Security', link: '/dynamic-table/security' },
        { text: 'Testing', link: '/dynamic-table/testing' },
        { text: 'Extending', link: '/dynamic-table/extending' },
        { text: 'Troubleshooting', link: '/dynamic-table/troubleshooting' },
        { text: 'Package Extraction', link: '/dynamic-table/package-extraction' },
        { text: 'Record References', link: '/dynamic-table/record-references' }
      ]
    }
  ],
  '/dynamic-record-view/': [
    {
      text: 'Dynamic Record View Engine',
      items: [
        { text: 'Overview', link: '/dynamic-record-view/README' },
        { text: 'Quick Start', link: '/dynamic-record-view/quick-start' },
        { text: 'Architecture', link: '/dynamic-record-view/architecture' },
        { text: 'Defining Record Views', link: '/dynamic-record-view/defining-record-views' },
        { text: 'Sections & Tabs', link: '/dynamic-record-view/sections-tabs' },
        { text: 'Content Blocks', link: '/dynamic-record-view/content-blocks' },
        { text: 'Fields', link: '/dynamic-record-view/fields' },
        { text: 'Record Resolution', link: '/dynamic-record-view/record-resolution' },
        { text: 'Embedded Tables', link: '/dynamic-record-view/embedded-tables' },
        { text: 'Relations', link: '/dynamic-record-view/relations' },
        { text: 'Sub Applications', link: '/dynamic-record-view/sub-applications' },
        { text: 'Relationship Actions', link: '/dynamic-record-view/relationship-actions' },
        { text: 'Relation Picker', link: '/dynamic-record-view/relation-picker' },
        { text: 'State Isolation', link: '/dynamic-record-view/state-isolation' },
        { text: 'Security', link: '/dynamic-record-view/security' },
        { text: 'Performance', link: '/dynamic-record-view/performance' },
        { text: 'Authorization', link: '/dynamic-record-view/authorization' },
        { text: 'Request Lifecycle', link: '/dynamic-record-view/request-lifecycle' },
        { text: 'Accessibility', link: '/dynamic-record-view/accessibility' },
        { text: 'Extending', link: '/dynamic-record-view/extending' },
        { text: 'Troubleshooting', link: '/dynamic-record-view/troubleshooting' },
        { text: 'Package Extraction', link: '/dynamic-record-view/package-extraction' },
        { text: 'Testing', link: '/dynamic-record-view/testing' }
      ]
    }
  ],
  '/record-references/': [
    {
      text: 'Record References',
      items: [
        { text: 'Overview & Guide', link: '/record-references/README' }
      ]
    }
  ],
  '/dynamic-form/': [
    {
      text: 'Dynamic Form Engine',
      items: [
        { text: 'Overview', link: '/dynamic-form/README' },
        { text: 'Quick Start', link: '/dynamic-form/quick-start' },
        { text: 'Fields', link: '/dynamic-form/fields' },
        { text: 'Hosting & Events', link: '/dynamic-form/hosting-and-events' },
        { text: 'Validation & Save', link: '/dynamic-form/validation-and-save' },
        { text: 'Testing', link: '/dynamic-form/testing' },
        { text: 'Record References (planned)', link: '/dynamic-form/record-references' }
      ]
    }
  ],
  '/permissions/': [
    {
      text: 'Permissions & Data Scope Engine',
      items: [
        { text: 'Overview', link: '/permissions/README' },
        { text: 'Data Scope', link: '/permissions/data-scope' },
        { text: 'Functional Permissions', link: '/permissions/functional-permissions' },
        { text: 'Testing', link: '/permissions/testing' }
      ]
    }
  ],
  '/user-guide/': [
    {
      text: 'User Guide',
      items: [
        { text: 'Managing Employee Permissions', link: '/user-guide/permissions' }
      ]
    }
  ]
}

const enNav = [
  { text: 'Home', link: '/' },
  {
    text: 'Technical Documentation',
    items: [
      { text: 'Dynamic Table', link: '/dynamic-table/README' },
      { text: 'Dynamic Record View', link: '/dynamic-record-view/README' },
      { text: 'Record References', link: '/record-references/README' },
      { text: 'Dynamic Form', link: '/dynamic-form/README' },
      { text: 'Permissions Engine', link: '/permissions/README' }
    ]
  },
  {
    text: 'User Guide',
    items: [
      { text: 'Managing Employee Permissions', link: '/user-guide/permissions' }
    ]
  }
]

// Only the Permissions engine and its user guide are translated so far — the
// other four engines are linked straight through to their English pages
// (VitePress allows cross-locale links) rather than left out of the Arabic
// nav entirely, so an Arabic-reading developer can still reach them.
const arSidebar = {
  '/ar/permissions/': [
    {
      text: 'محرك الصلاحيات ونطاق البيانات',
      items: [
        { text: 'نظرة عامة', link: '/ar/permissions/README' },
        { text: 'نطاق البيانات', link: '/ar/permissions/data-scope' },
        { text: 'الصلاحيات الوظيفية', link: '/ar/permissions/functional-permissions' },
        { text: 'الاختبارات', link: '/ar/permissions/testing' }
      ]
    }
  ],
  '/ar/user-guide/': [
    {
      text: 'دليل المستخدم',
      items: [
        { text: 'إدارة صلاحيات الموظفين', link: '/ar/user-guide/permissions' }
      ]
    }
  ]
}

const arNav = [
  { text: 'الرئيسية', link: '/ar/' },
  {
    text: 'التوثيق التقني',
    items: [
      { text: 'الجداول الديناميكية (EN)', link: '/dynamic-table/README' },
      { text: 'صفحات العرض (EN)', link: '/dynamic-record-view/README' },
      { text: 'الروابط المرجعية (EN)', link: '/record-references/README' },
      { text: 'النماذج الديناميكية (EN)', link: '/dynamic-form/README' },
      { text: 'محرك الصلاحيات', link: '/ar/permissions/README' }
    ]
  },
  {
    text: 'دليل المستخدم',
    items: [
      { text: 'إدارة صلاحيات الموظفين', link: '/ar/user-guide/permissions' }
    ]
  }
]

export default defineConfig({
  title: 'TagERP Documentation',
  description: 'Technical Documentation for TagERP Engines',
  base: '/docs/',
  outDir: '../public/docs',
  cleanUrls: true,

  locales: {
    root: {
      label: 'English',
      lang: 'en',
      dir: 'ltr',
      themeConfig: {
        nav: enNav,
        sidebar: enSidebar
      }
    },
    ar: {
      label: 'العربية',
      lang: 'ar',
      dir: 'rtl',
      link: '/ar/',
      themeConfig: {
        nav: arNav,
        sidebar: arSidebar
      }
    }
  },

  themeConfig: {
    search: {
      provider: 'local'
    }
  }
})
