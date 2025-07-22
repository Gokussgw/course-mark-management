import { createRouter, createWebHistory } from 'vue-router'
import store from '../store'

// Lazy-loaded components
const Login = () => import('../views/Login.vue')
const Register = () => import('../views/Register.vue')
const LecturerDashboard = () => import('../views/lecturer/Dashboard.vue')
const StudentDashboard = () => import('../views/student/Dashboard.vue')
const AdvisorDashboard = () => import('../views/advisor/Dashboard.vue')
const AdminDashboard = () => import('../views/admin/Dashboard.vue')
const CourseDetail = () => import('../views/lecturer/CourseDetail.vue')
const EnrollmentManagement = () => import('../views/lecturer/EnrollmentManagement.vue')
const StudentDetail = () => import('../views/lecturer/StudentDetail.vue')
const AssessmentForm = () => import('../views/lecturer/AssessmentForm.vue')
const CSVImport = () => import('../views/lecturer/CSVImport.vue')
const MarksManagement = () => import('../views/lecturer/MarksManagement.vue')
const StudentFeedback = () => import('../views/lecturer/StudentFeedback.vue')
const StudentMarksView = () => import('../views/student/CourseMarks.vue')
const StudentSimulation = () => import('../views/student/MarkSimulation.vue')
const RemarkRequest = () => import('../views/student/RemarkRequest.vue')
const StudentFeedbackView = () => import('../views/student/FeedbackView.vue')
const CourseComparison = () => import('../views/student/CourseComparison.vue')
const AdviseeList = () => import('../views/advisor/AdviseeList.vue')
const AdviseeDetail = () => import('../views/advisor/AdviseeDetail.vue')
const StudentRankingPage = () => import('../views/advisor/StudentRankingPage.vue')
const AtRiskStudents = () => import('../views/advisor/AtRiskStudents.vue')
const AdvisorFeedbackView = () => import('../views/advisor/FeedbackView.vue')
const AdvisorComparisons = () => import('../views/advisor/AdvisorComparisons.vue')
const MarkBreakdown = () => import('../views/shared/MarkBreakdown.vue')
const UserManagement = () => import('../views/admin/UserManagement.vue')
const AdminEnrollmentManagement = () => import('../views/admin/EnrollmentManagement.vue')
const SystemLogs = () => import('../views/admin/SystemLogs.vue')
const NotFound = () => import('../views/NotFound.vue')

const routes = [
  {
    path: '/',
    redirect: () => {
      const userRole = store.getters['auth/userRole'];
      if (userRole === 'lecturer') {
        return '/lecturer/dashboard';
      } else if (userRole === 'student') {
        return '/student/dashboard';
      } else if (userRole === 'advisor') {
        return '/advisor/dashboard';
      } else if (userRole === 'admin') {
        return '/admin/dashboard';
      } else {
        return '/login';
      }
    }
  },
  {
    path: '/login',
    name: 'Login',
    component: Login,
    meta: { requiresGuest: true }
  },
  {
    path: '/register',
    name: 'Register',
    component: Register,
    meta: { requiresGuest: true }
  },
  // Lecturer routes
  {
    path: '/lecturer/dashboard',
    name: 'LecturerDashboard',
    component: LecturerDashboard,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/course/:id',
    name: 'CourseDetail',
    component: CourseDetail,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/course/:courseId/enrollments',
    name: 'EnrollmentManagement',
    component: EnrollmentManagement,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/student/:id',
    name: 'StudentDetail',
    component: StudentDetail,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/assessment/create',
    name: 'CreateAssessment',
    component: AssessmentForm,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/assessment/edit/:id',
    name: 'EditAssessment',
    component: AssessmentForm,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/csv-import',
    name: 'CSVImport',
    component: CSVImport,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/course/:courseId/marks',
    name: 'MarksManagement',
    component: MarksManagement,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/marks',
    name: 'MarksManagementGeneral',
    component: MarksManagement,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/feedback',
    name: 'StudentFeedback',
    component: StudentFeedback,
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  {
    path: '/lecturer/breakdown/:courseId',
    name: 'LecturerMarkBreakdown',
    component: MarkBreakdown,
    props: route => ({ courseId: parseInt(route.params.courseId), userRole: 'lecturer' }),
    meta: { requiresAuth: true, role: 'lecturer' }
  },
  // Student routes
  {
    path: '/student/dashboard',
    name: 'StudentDashboard',
    component: StudentDashboard,
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/course/:id',
    name: 'StudentMarksView',
    component: StudentMarksView,
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/simulation/:id',
    name: 'StudentSimulation',
    component: StudentSimulation,
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/remark/:markId',
    name: 'RemarkRequest',
    component: RemarkRequest,
    props: route => ({ markId: parseInt(route.params.markId) }),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/feedback',
    name: 'StudentFeedbackView',
    component: StudentFeedbackView,
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/breakdown/:courseId',
    name: 'StudentMarkBreakdown',
    component: MarkBreakdown,
    props: route => ({ courseId: parseInt(route.params.courseId), userRole: 'student' }),
    meta: { requiresAuth: true, role: 'student' }
  },
  {
    path: '/student/comparison',
    name: 'StudentComparison',
    component: CourseComparison,
    meta: { requiresAuth: true, role: 'student' }
  },
  // Advisor routes
  {
    path: '/advisor/dashboard',
    name: 'AdvisorDashboard',
    component: AdvisorDashboard,
    meta: { requiresAuth: true, role: 'advisor' }
  },
  {
    path: '/advisor/advisees',
    name: 'AdviseeList',
    component: AdviseeList,
    meta: { requiresAuth: true, role: 'advisor' }
  },
  {
    path: '/advisor/advisee/:id',
    name: 'AdviseeDetail',
    component: AdviseeDetail,
    meta: { requiresAuth: true, role: 'advisor' }
  },
  {
    path: '/advisor/advisee/:id/ranking',
    name: 'StudentRankingPage',
    component: StudentRankingPage,
    meta: { requiresAuth: true, role: 'advisor' }
  },
  {
    path: '/advisor/at-risk',
    name: 'AtRiskStudents',
    component: AtRiskStudents,
    meta: { requiresAuth: true, role: 'advisor' }
  },
  {
    path: '/advisor/feedback',
    name: 'AdvisorFeedbackView',
    component: AdvisorFeedbackView,
    meta: { requiresAuth: true, role: 'advisor' }
  },
  {
    path: '/advisor/breakdown/:courseId',
    name: 'AdvisorMarkBreakdown',
    component: MarkBreakdown,
    props: route => ({ courseId: parseInt(route.params.courseId), userRole: 'advisor' }),
    meta: { requiresAuth: true, role: 'advisor' }
  },
  {
    path: '/advisor/comparisons',
    name: 'AdvisorComparisons',
    component: AdvisorComparisons,
    meta: { requiresAuth: true, role: 'advisor' }
  },
  // Admin routes
  {
    path: '/admin/dashboard',
    name: 'AdminDashboard',
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/users',
    name: 'UserManagement',
    component: UserManagement,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/enrollments',
    name: 'AdminEnrollmentManagement',
    component: AdminEnrollmentManagement,
    meta: { requiresAuth: true, role: 'admin' }
  },
  {
    path: '/admin/logs',
    name: 'SystemLogs',
    component: SystemLogs,
    meta: { requiresAuth: true, role: 'admin' }
  },
  // 404 page
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: NotFound
  }
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const isAuthenticated = store.getters['auth/isAuthenticated'];
  const userRole = store.getters['auth/userRole'];
  
  // Check if the route requires authentication
  if (to.matched.some(record => record.meta.requiresAuth)) {
    if (!isAuthenticated) {
      next({ name: 'Login' });
    } else if (to.meta.role && to.meta.role !== userRole) {
      // Redirect to the appropriate dashboard if role doesn't match
      next({ path: '/' });
    } else {
      next();
    }
  }
  // Check if the route requires guest access (like login page)
  else if (to.matched.some(record => record.meta.requiresGuest)) {
    if (isAuthenticated) {
      next({ path: '/' });
    } else {
      next();
    }
  }
  else {
    next();
  }
});

export default router;
