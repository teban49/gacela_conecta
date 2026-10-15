import { useEffect, useMemo, useState } from 'react';
import { Shield, Layers3, LockKeyhole, UsersRound, CheckCircle2, Save, Plus, Edit, Power, PowerOff, X } from 'lucide-react';
import { securityAPI } from '../services/securityAPI';

const tabs = [
  { id: 'applications', label: 'Aplicaciones', icon: Layers3 },
  { id: 'permissions', label: 'Permisos', icon: LockKeyhole },
  { id: 'roles', label: 'Roles', icon: Shield },
  { id: 'users', label: 'Usuarios', icon: UsersRound },
];

const SecurityPage = () => {
  const [activeTab, setActiveTab] = useState('applications');
  const [applications, setApplications] = useState([]);
  const [permissions, setPermissions] = useState([]);
  const [roles, setRoles] = useState([]);
  const [users, setUsers] = useState([]);
  const [jobTitles, setJobTitles] = useState([]);
  const [rolePermissionsByRole, setRolePermissionsByRole] = useState({});
  const [userRoleSelections, setUserRoleSelections] = useState({});
  const [loading, setLoading] = useState(true);

  // Modal states
  const [showApplicationModal, setShowApplicationModal] = useState(false);
  const [showPermissionModal, setShowPermissionModal] = useState(false);
  const [showRoleModal, setShowRoleModal] = useState(false);
  const [showUserModal, setShowUserModal] = useState(false);
  const [showUserPermissionsModal, setShowUserPermissionsModal] = useState(false);

  const [editingItem, setEditingItem] = useState(null);
  const [formData, setFormData] = useState({});
  const [selectedUserForPermissions, setSelectedUserForPermissions] = useState(null);
  const [userEffectivePermissions, setUserEffectivePermissions] = useState([]);

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [applicationsRes, permissionsRes, rolesRes, usersRes, jobTitlesRes] = await Promise.all([
        securityAPI.getApplications(),
        securityAPI.getPermissions(),
        securityAPI.getRoles(),
        securityAPI.getUsers(),
        securityAPI.getJobTitles(),
      ]);

      const applicationsData = applicationsRes.data?.data ?? [];
      const permissionsData = permissionsRes.data?.data ?? [];
      const rolesData = rolesRes.data?.data ?? [];
      const usersData = usersRes.data?.data ?? [];
      const jobTitlesData = jobTitlesRes.data?.data ?? [];

      setApplications(applicationsData);
      setPermissions(permissionsData);
      setRoles(rolesData);
      setUsers(usersData);
      setJobTitles(jobTitlesData);

      const roleDetails = await Promise.all(
        rolesData.map(async (role) => {
          const response = await securityAPI.getRoleById(role.Id);
          return [role.Id, response.data?.data?.permissions ?? []];
        })
      );

      const roleMap = Object.fromEntries(roleDetails);
      setRolePermissionsByRole(roleMap);

      const initialUserRoles = Object.fromEntries(
        usersData.map((user) => [user.Id, String(user.RolId ?? '')])
      );
      setUserRoleSelections(initialUserRoles);
    } catch (error) {
      console.error('Error cargando seguridad:', error);
    } finally {
      setLoading(false);
    }
  };

  const permissionsByApp = useMemo(() => {
    return applications.reduce((acc, app) => {
      acc[app.Id] = permissions.filter((permission) => Number(permission.AplicacionId) === Number(app.Id));
      return acc;
    }, {});
  }, [applications, permissions]);

  const toggleRolePermission = (roleId, permissionId) => {
    const current = rolePermissionsByRole[roleId] ?? [];
    const alreadySelected = current.some((permission) => Number(permission.Id) === Number(permissionId));

    const nextPermissions = alreadySelected
      ? current.filter((permission) => Number(permission.Id) !== Number(permissionId))
      : [...current, permissions.find((permission) => Number(permission.Id) === Number(permissionId))].filter(Boolean);

    setRolePermissionsByRole((prev) => ({
      ...prev,
      [roleId]: nextPermissions,
    }));
  };

  const saveRolePermissions = async (roleId) => {
    try {
      const permissionIds = (rolePermissionsByRole[roleId] ?? []).map((permission) => Number(permission.Id));
      const response = await securityAPI.syncRolePermissions(roleId, permissionIds);
      
      if (response.data?.success) {
        alert(response.data?.message || 'Permisos del rol actualizados correctamente.');
        const updatedRole = await securityAPI.getRoleById(roleId);
        setRolePermissionsByRole((prev) => ({
          ...prev,
          [roleId]: updatedRole.data?.data?.permissions ?? [],
        }));
      } else {
        alert(response.data?.message || 'No fue posible guardar los permisos del rol.');
      }
    } catch (error) {
      console.error('Error guardando permisos del rol:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible guardar los permisos del rol.';
      alert(errorMessage);
    }
  };

  const selectUserRole = (userId, roleId) => {
    setUserRoleSelections((prev) => {
      return {
        ...prev,
        [userId]: String(roleId),
      };
    });
  };

  const saveUserRoles = async (userId) => {
    try {
      const roleId = Number(userRoleSelections[userId]);
      if (!Number.isInteger(roleId) || roleId < 1) {
        alert('Selecciona un rol para el usuario.');
        return;
      }
      const response = await securityAPI.setUserRole(userId, roleId);
      
      if (response.data?.success) {
        alert(response.data?.message || 'Roles del usuario actualizados correctamente.');
        const usersRes = await securityAPI.getUsers();
        const usersData = usersRes.data?.data ?? [];
        setUsers(usersData);
        const nextUserRoles = Object.fromEntries(
          usersData.map((u) => [u.Id, String(u.RolId ?? '')])
        );
        setUserRoleSelections(nextUserRoles);
      } else {
        alert(response.data?.message || 'No fue posible guardar los roles del usuario.');
      }
    } catch (error) {
      console.error('Error guardando roles del usuario:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible guardar los roles del usuario.';
      alert(errorMessage);
    }
  };

  const handleCreateApplication = () => {
    setEditingItem(null);
    setFormData({
      Nombre: '',
      Codigo: '',
      Ruta: '',
      Color: '#6366f1',
      Icono: 'layout-dashboard',
      OrdenMenu: 1,
      Visible: true,
      Estado: true
    });
    setShowApplicationModal(true);
  };

  const handleEditApplication = (app) => {
    setEditingItem(app);
    setFormData({
      Nombre: app.Nombre,
      Codigo: app.Codigo,
      Ruta: app.Ruta || '',
      Color: app.Color || '#6366f1',
      Icono: app.Icono || '',
      OrdenMenu: app.OrdenMenu || 0,
      Visible: Boolean(app.Visible),
      Estado: Boolean(app.Estado)
    });
    setShowApplicationModal(true);
  };

  const handleSaveApplication = async () => {
    try {
      let response;
      if (editingItem) {
        response = await securityAPI.updateApplication(editingItem.Id, formData);
      } else {
        response = await securityAPI.createApplication(formData);
      }

      if (response.data?.success) {
        alert(response.data?.message || 'Operación exitosa');
        setShowApplicationModal(false);
        const applicationsRes = await securityAPI.getApplications();
        setApplications(applicationsRes.data?.data ?? []);
      } else {
        alert(response.data?.message || 'Error en la operación');
      }
    } catch (error) {
      console.error('Error guardando aplicación:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible guardar la aplicación.';
      alert(errorMessage);
    }
  };

  const handleToggleApplicationStatus = async (app) => {
    try {
      const response = await securityAPI.changeApplicationStatus(app.Id, !app.Estado);
      if (response.data?.success) {
        const applicationsRes = await securityAPI.getApplications();
        setApplications(applicationsRes.data?.data ?? []);
      } else {
        alert(response.data?.message || 'Error cambiando estado');
      }
    } catch (error) {
      console.error('Error cambiando estado:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible cambiar el estado.';
      alert(errorMessage);
    }
  };

  const handleToggleApplicationVisibility = async (app) => {
    try {
      const response = await securityAPI.changeApplicationVisibility(app.Id, !app.Visible);
      if (response.data?.success) {
        const applicationsRes = await securityAPI.getApplications();
        setApplications(applicationsRes.data?.data ?? []);
      } else {
        alert(response.data?.message || 'Error cambiando visibilidad');
      }
    } catch (error) {
      console.error('Error cambiando visibilidad:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible cambiar la visibilidad.';
      alert(errorMessage);
    }
  };

  const handleCreatePermission = (applicationId) => {
    setEditingItem(null);
    setFormData({
      AplicacionId: applicationId,
      Modulo: '',
      Accion: '',
      Descripcion: '',
      CodigoUnico: '',
      Estado: true
    });
    setShowPermissionModal(true);
  };

  const handleEditPermission = (permission) => {
    setEditingItem(permission);
    setFormData({
      AplicacionId: permission.AplicacionId,
      Modulo: permission.Modulo,
      Accion: permission.Accion,
      Descripcion: permission.Descripcion || '',
      CodigoUnico: permission.CodigoUnico,
      Estado: Boolean(permission.Estado)
    });
    setShowPermissionModal(true);
  };

  const handleSavePermission = async () => {
    try {
      let response;
      if (editingItem) {
        response = await securityAPI.updatePermission(editingItem.Id, formData);
      } else {
        response = await securityAPI.createPermission(formData);
      }

      if (response.data?.success) {
        alert(response.data?.message || 'Operación exitosa');
        setShowPermissionModal(false);
        const [applicationsRes, permissionsRes] = await Promise.all([
          securityAPI.getApplications(),
          securityAPI.getPermissions()
        ]);
        setApplications(applicationsRes.data?.data ?? []);
        setPermissions(permissionsRes.data?.data ?? []);
      } else {
        alert(response.data?.message || 'Error en la operación');
      }
    } catch (error) {
      console.error('Error guardando permiso:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible guardar el permiso.';
      alert(errorMessage);
    }
  };

  const handleTogglePermissionStatus = async (permission) => {
    try {
      const response = await securityAPI.changePermissionStatus(permission.Id, !permission.Estado);
      if (response.data?.success) {
        const permissionsRes = await securityAPI.getPermissions();
        setPermissions(permissionsRes.data?.data ?? []);
      } else {
        alert(response.data?.message || 'Error cambiando estado');
      }
    } catch (error) {
      console.error('Error cambiando estado:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible cambiar el estado.';
      alert(errorMessage);
    }
  };

  const handleCreateRole = () => {
    setEditingItem(null);
    setFormData({
      Nombre: '',
      Codigo: '',
      Descripcion: '',
      NivelAcceso: 1,
      Estado: true
    });
    setShowRoleModal(true);
  };

  const handleEditRole = (role) => {
    setEditingItem(role);
    setFormData({
      Nombre: role.Nombre,
      Codigo: role.Codigo,
      Descripcion: role.Descripcion || '',
      NivelAcceso: role.NivelAcceso || 1,
      Estado: Boolean(role.Estado)
    });
    setShowRoleModal(true);
  };

  const handleSaveRole = async () => {
    try {
      let response;
      if (editingItem) {
        response = await securityAPI.updateRole(editingItem.Id, formData);
      } else {
        response = await securityAPI.createRole(formData);
      }

      if (response.data?.success) {
        alert(response.data?.message || 'Operación exitosa');
        setShowRoleModal(false);
        const rolesRes = await securityAPI.getRoles();
        setRoles(rolesRes.data?.data ?? []);
      } else {
        alert(response.data?.message || 'Error en la operación');
      }
    } catch (error) {
      console.error('Error guardando rol:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible guardar el rol.';
      alert(errorMessage);
    }
  };

  const handleToggleRoleStatus = async (role) => {
    try {
      const response = await securityAPI.changeRoleStatus(role.Id, !role.Estado);
      if (response.data?.success) {
        const rolesRes = await securityAPI.getRoles();
        setRoles(rolesRes.data?.data ?? []);
      } else {
        alert(response.data?.message || 'Error cambiando estado');
      }
    } catch (error) {
      console.error('Error cambiando estado:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible cambiar el estado.';
      alert(errorMessage);
    }
  };

  const handleCreateUser = () => {
    setEditingItem(null);
    setFormData({
      GrupoId: 1,
      EmpresaId: 1,
      CiudadId: 1,
      AgenciaId: 1,
      AreaId: 1,
      CargoId: 1,
      Nombres: '',
      Apellidos: '',
      EmailCorporativo: '',
      Telefono: '',
      CargoJefeDirectoId: '',
      Estado: true
    });
    setShowUserModal(true);
  };

  const handleEditUser = (user) => {
    setEditingItem(user);
    setFormData({
      GrupoId: user.GrupoId || 1,
      EmpresaId: user.EmpresaId || 1,
      CiudadId: user.CiudadId || 1,
      AgenciaId: user.AgenciaId || 1,
      AreaId: user.AreaId || 1,
      CargoId: user.CargoId || 1,
      Nombres: user.Nombres,
      Apellidos: user.Apellidos,
      EmailCorporativo: user.EmailCorporativo,
      Telefono: user.Telefono || '',
      CargoJefeDirectoId: user.CargoJefeDirectoId ? String(user.CargoJefeDirectoId) : '',
      Estado: Boolean(user.Estado)
    });
    setShowUserModal(true);
  };

  const handleSaveUser = async () => {
    try {
      let response;
      if (editingItem) {
        response = await securityAPI.updateUser(editingItem.Id, formData);
      } else {
        response = await securityAPI.createUser(formData);
      }

      if (response.data?.success) {
        alert(response.data?.message || 'Operación exitosa');
        setShowUserModal(false);
        const usersRes = await securityAPI.getUsers();
        setUsers(usersRes.data?.data ?? []);
      } else {
        alert(response.data?.message || 'Error en la operación');
      }
    } catch (error) {
      console.error('Error guardando usuario:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible guardar el usuario.';
      alert(errorMessage);
    }
  };

  const handleToggleUserStatus = async (user) => {
    try {
      const response = await securityAPI.changeUserStatus(user.Id, !user.Estado);
      if (response.data?.success) {
        const usersRes = await securityAPI.getUsers();
        setUsers(usersRes.data?.data ?? []);
      } else {
        alert(response.data?.message || 'Error cambiando estado');
      }
    } catch (error) {
      console.error('Error cambiando estado:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible cambiar el estado.';
      alert(errorMessage);
    }
  };

  const handleViewUserPermissions = async (user) => {
    try {
      setSelectedUserForPermissions(user);
      const response = await securityAPI.getUserPermissions(user.Id);
      const perms = response.data?.data ?? [];
      setUserEffectivePermissions(perms);
      setShowUserPermissionsModal(true);
    } catch (error) {
      console.error('Error obteniendo permisos:', error);
      const errorMessage = error.response?.data?.message || error.message || 'No fue posible obtener los permisos.';
      alert(errorMessage);
    }
  };

  const renderApplications = () => (
    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="mb-4 flex items-center justify-between">
        <h3 className="text-lg font-bold text-slate-800">Aplicaciones Registradas</h3>
        <button 
          onClick={handleCreateApplication}
          className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 transition"
        >
          <Plus size={16} />
          Nueva Aplicación
        </button>
      </div>
      <div className="overflow-x-auto">
        <table className="min-w-full text-sm">
          <thead>
            <tr className="text-left text-slate-500 border-b border-slate-200">
              <th className="pb-3 font-semibold">Nombre</th>
              <th className="pb-3 font-semibold">Código</th>
              <th className="pb-3 font-semibold">Ruta</th>
              <th className="pb-3 font-semibold">Color</th>
              <th className="pb-3 font-semibold">Icono</th>
              <th className="pb-3 font-semibold">Orden</th>
              <th className="pb-3 font-semibold">Visible</th>
              <th className="pb-3 font-semibold">Estado</th>
              <th className="pb-3 font-semibold">Acciones</th>
            </tr>
          </thead>
          <tbody>
            {applications.map((app) => (
              <tr key={app.Id} className="border-b border-slate-100 hover:bg-slate-50">
                <td className="py-3 font-semibold text-slate-800">{app.Nombre}</td>
                <td className="py-3 text-slate-600 font-mono text-xs">{app.Codigo}</td>
                <td className="py-3 text-slate-600">{app.Ruta}</td>
                <td className="py-3">
                  <span className="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold" style={{ backgroundColor: `${app.Color}20`, color: app.Color }}>
                    <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: app.Color }}></span>
                    {app.Color}
                  </span>
                </td>
                <td className="py-3 text-slate-600">{app.Icono}</td>
                <td className="py-3 text-slate-600">{app.OrdenMenu}</td>
                <td className="py-3">
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold ${app.Visible ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700'}`}>
                    {app.Visible ? 'Sí' : 'No'}
                  </span>
                </td>
                <td className="py-3">
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold ${app.Estado ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'}`}>
                    {app.Estado ? 'Activo' : 'Inactivo'}
                  </span>
                </td>
                <td className="py-3">
                  <div className="flex gap-2">
                    <button 
                      onClick={() => handleEditApplication(app)}
                      className="rounded-lg bg-blue-50 p-2 text-blue-600 hover:bg-blue-100"
                      title="Editar"
                    >
                      <Edit size={14} />
                    </button>
                    <button 
                      onClick={() => handleToggleApplicationVisibility(app)}
                      className="rounded-lg bg-purple-50 p-2 text-purple-600 hover:bg-purple-100"
                      title={app.Visible ? 'Ocultar en menú' : 'Mostrar en menú'}
                    >
                      {app.Visible ? '👁️' : '🙈'}
                    </button>
                    <button 
                      onClick={() => handleToggleApplicationStatus(app)}
                      className="rounded-lg bg-orange-50 p-2 text-orange-600 hover:bg-orange-100"
                      title={app.Estado ? 'Desactivar' : 'Activar'}
                    >
                      {app.Estado ? <PowerOff size={14} /> : <Power size={14} />}
                    </button>
                  </div>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );

  const renderPermissions = () => (
    <div className="space-y-4">
      {applications.map((app) => (
        <div key={app.Id} className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
          <div className="mb-4 flex items-center justify-between">
            <div className="flex items-center gap-3">
              <span className="w-3 h-3 rounded-full" style={{ backgroundColor: app.Color || '#6366f1' }}></span>
              <h3 className="text-lg font-bold text-slate-800">{app.Nombre}</h3>
            </div>
            <div className="flex items-center gap-3">
              <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{permissionsByApp[app.Id]?.length ?? 0} permisos</span>
              <button 
                onClick={() => handleCreatePermission(app.Id)}
                className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-700 transition"
              >
                <Plus size={14} />
                Nuevo Permiso
              </button>
            </div>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            {(permissionsByApp[app.Id] ?? []).map((permission) => (
              <div key={permission.Id} className="flex items-center justify-between rounded-xl bg-orange-50/70 border border-orange-100 px-3 py-2.5">
                <div className="flex flex-col gap-0.5">
                  <div className="flex items-center gap-2">
                    <CheckCircle2 size={14} className="text-orange-600" />
                    <span className="text-sm font-semibold text-slate-800">{permission.Accion}</span>
                  </div>
                  <span className="text-xs font-mono text-orange-700 ml-5.5">{permission.CodigoUnico}</span>
                </div>
                <div className="flex gap-1.5">
                  <button 
                    onClick={() => handleEditPermission(permission)}
                    className="rounded-lg bg-blue-50 p-1.5 text-blue-600 hover:bg-blue-100"
                    title="Editar"
                  >
                    <Edit size={12} />
                  </button>
                  <button 
                    onClick={() => handleTogglePermissionStatus(permission)}
                    className={`rounded-lg p-1.5 ${permission.Estado ? 'bg-orange-100 text-orange-700 hover:bg-orange-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200'}`}
                    title={permission.Estado ? 'Desactivar' : 'Activar'}
                  >
                    {permission.Estado ? <PowerOff size={12} /> : <Power size={12} />}
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );

  const renderRoles = () => (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h3 className="text-lg font-bold text-slate-800">Roles del Sistema y Matriz de Permisos</h3>
        <button 
          onClick={handleCreateRole}
          className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 transition"
        >
          <Plus size={16} />
          Nuevo Rol
        </button>
      </div>
      {roles.map((role) => {
        const selectedRolePermissions = rolePermissionsByRole[role.Id] ?? [];
        return (
          <div key={role.Id} className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-4 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div>
                  <h3 className="text-lg font-bold text-slate-800">{role.Nombre}</h3>
                  <p className="text-xs text-slate-500 font-mono">{role.Codigo} • Nivel de acceso: {role.NivelAcceso}</p>
                </div>
                <span className={`rounded-full px-3 py-1 text-xs font-semibold ${role.Estado ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'}`}>
                  {role.Estado ? 'Activo' : 'Inactivo'}
                </span>
              </div>
              <div className="flex gap-2">
                <button 
                  onClick={() => handleEditRole(role)}
                  className="rounded-lg bg-blue-50 p-2 text-blue-600 hover:bg-blue-100"
                  title="Editar"
                >
                  <Edit size={14} />
                </button>
                <button 
                  onClick={() => handleToggleRoleStatus(role)}
                  className="rounded-lg bg-orange-50 p-2 text-orange-600 hover:bg-orange-100"
                  title={role.Estado ? 'Desactivar' : 'Activar'}
                >
                  {role.Estado ? <PowerOff size={14} /> : <Power size={14} />}
                </button>
                <button
                  onClick={() => saveRolePermissions(role.Id)}
                  className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                >
                  <Save size={14} />
                  Guardar Permisos
                </button>
              </div>
            </div>
            <div className="space-y-4 border-t border-slate-100 pt-4">
              {applications.map((app) => {
                const appPermissions = permissionsByApp[app.Id] ?? [];
                if (!appPermissions.length) {
                  return null;
                }
                return (
                  <div key={`${role.Id}-${app.Id}`} className="rounded-2xl bg-slate-50/80 p-4 border border-slate-100">
                    <div className="mb-2.5 text-sm font-bold text-slate-800 flex items-center gap-2">
                      <span className="w-2.5 h-2.5 rounded-full" style={{ backgroundColor: app.Color || '#6366f1' }}></span>
                      {app.Nombre}
                    </div>
                    <div className="flex flex-wrap gap-2">
                      {appPermissions.map((permission) => {
                        const enabled = selectedRolePermissions.some((selectedPermission) => Number(selectedPermission.Id) === Number(permission.Id));
                        return (
                          <label key={permission.Id} className={`inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold cursor-pointer border transition ${
                            enabled ? 'bg-orange-50 text-orange-700 border-orange-200' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100'
                          }`}>
                            <input
                              type="checkbox"
                              checked={enabled}
                              onChange={() => toggleRolePermission(role.Id, permission.Id)}
                              className="rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                            />
                            {permission.Accion} ({permission.CodigoUnico})
                          </label>
                        );
                      })}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        );
      })}
    </div>
  );

  const renderUsers = () => (
    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div className="mb-6 flex items-center justify-between">
        <h3 className="text-lg font-bold text-slate-800">Administración de Usuarios</h3>
        <button 
          onClick={handleCreateUser}
          className="inline-flex items-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 transition"
        >
          <Plus size={16} />
          Nuevo Usuario
        </button>
      </div>
      <div className="space-y-4">
        {users.map((user) => (
          <div key={user.Id} className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm hover:border-slate-300 transition">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div className="flex-1">
                <div className="flex items-center gap-3">
                  <div className="text-lg font-bold text-slate-900">{user.Nombres} {user.Apellidos}</div>
                  <span className={`rounded-full px-3 py-1 text-xs font-semibold ${user.Estado ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200'}`}>
                    {user.Estado ? 'Activo' : 'Inactivo'}
                  </span>
                </div>
                <div className="mt-1 text-sm text-slate-500">{user.EmailCorporativo}</div>
                <div className="mt-1 text-xs text-slate-500">
                  Cargo de jefe directo: {user.JefeDirectoCargoNombre || 'Sin configurar'}
                </div>
                <div className="mt-1 text-xs text-slate-500">
                  Rol del sistema: {user.RolNombre || 'Sin configurar'}
                </div>
                {user.Telefono && (
                  <div className="mt-1 text-xs text-slate-400">Tel: {user.Telefono}</div>
                )}
              </div>
              <div className="flex flex-col gap-3">
                <div className="flex flex-wrap gap-2 items-center">
                  <span className="text-xs font-semibold text-slate-500">Roles:</span>
                  {roles.map((role) => {
                    const checked = userRoleSelections[user.Id] === String(role.Id);
                    return (
                      <label key={`${user.Id}-${role.Id}`} className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold cursor-pointer border transition ${
                        checked ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'
                      }`}>
                        <input
                          type="radio"
                          name={`user-role-${user.Id}`}
                          checked={checked}
                          onChange={() => selectUserRole(user.Id, role.Id)}
                          className="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        {role.Nombre}
                      </label>
                    );
                  })}
                </div>
                <div className="flex gap-2 justify-end">
                  <button
                    onClick={() => saveUserRoles(user.Id)}
                    className="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                  >
                    <Save size={13} />
                    Guardar Rol
                  </button>
                  <button 
                    onClick={() => handleViewUserPermissions(user)}
                    className="inline-flex items-center gap-2 rounded-xl bg-purple-50 px-4 py-2 text-xs font-semibold text-purple-700 hover:bg-purple-100"
                  >
                    <LockKeyhole size={13} />
                    Permisos Efectivos
                  </button>
                  <button 
                    onClick={() => handleEditUser(user)}
                    className="inline-flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                  >
                    <Edit size={13} />
                    Editar
                  </button>
                  <button 
                    onClick={() => handleToggleUserStatus(user)}
                    className={`inline-flex items-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold ${user.Estado ? 'bg-orange-50 text-orange-700 hover:bg-orange-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'}`}
                  >
                    {user.Estado ? <PowerOff size={13} /> : <Power size={13} />}
                    {user.Estado ? 'Desactivar' : 'Activar'}
                  </button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );

  if (loading) {
    return (
      <div className="min-h-[60vh] flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"></div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto p-6 space-y-6">
      <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 className="text-3xl font-bold text-slate-900">Seguridad GCP</h1>
            <p className="text-slate-500">Gestión de aplicaciones, matriz de permisos RBAC, roles y usuarios.</p>
          </div>
        </div>

        <div className="mt-6 flex flex-wrap gap-2 border-b border-slate-100 pb-3">
          {tabs.map((tab) => {
            const Icon = tab.icon;
            const isActive = activeTab === tab.id;
            return (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold transition ${
                  isActive ? 'bg-orange-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                }`}
              >
                <Icon size={16} />
                {tab.label}
              </button>
            );
          })}
        </div>
      </div>

      {activeTab === 'applications' && renderApplications()}
      {activeTab === 'permissions' && renderPermissions()}
      {activeTab === 'roles' && renderRoles()}
      {activeTab === 'users' && renderUsers()}

      {/* Modal Permisos Efectivos del Usuario */}
      {showUserPermissionsModal && selectedUserForPermissions && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="max-w-2xl w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl max-h-[85vh] flex flex-col">
            <div className="mb-4 flex items-center justify-between border-b border-slate-100 pb-4">
              <div>
                <h3 className="text-xl font-bold text-slate-900">
                  Permisos Efectivos
                </h3>
                <p className="text-sm text-slate-500">
                  {selectedUserForPermissions.Nombres} {selectedUserForPermissions.Apellidos} ({selectedUserForPermissions.EmailCorporativo})
                </p>
              </div>
              <button 
                onClick={() => setShowUserPermissionsModal(false)}
                className="rounded-lg bg-slate-100 p-2 text-slate-600 hover:bg-slate-200"
              >
                <X size={18} />
              </button>
            </div>
            <div className="overflow-y-auto space-y-4 pr-1 flex-1">
              {userEffectivePermissions.length === 0 ? (
                <div className="p-8 text-center text-slate-500">
                  Este usuario no posee permisos activos actualmente.
                </div>
              ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  {userEffectivePermissions.map((perm) => (
                    <div key={perm.Id || perm.CodigoUnico} className="p-3 rounded-2xl bg-orange-50/60 border border-orange-100 flex flex-col gap-1">
                      <span className="text-xs font-bold text-orange-800 uppercase tracking-wider">{perm.AplicacionNombre || 'Sistema'}</span>
                      <span className="text-sm font-semibold text-slate-800">{perm.Accion || perm.Modulo}</span>
                      <span className="text-xs font-mono text-orange-600">{perm.CodigoUnico}</span>
                    </div>
                  ))}
                </div>
              )}
            </div>
            <div className="mt-4 border-t border-slate-100 pt-4 flex justify-end">
              <button
                onClick={() => setShowUserPermissionsModal(false)}
                className="px-5 py-2.5 bg-slate-900 text-white rounded-xl text-sm font-semibold hover:bg-slate-800"
              >
                Cerrar
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Usuario */}
      {showUserModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="max-w-md w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
            <div className="mb-4 flex items-center justify-between">
              <h3 className="text-xl font-bold text-slate-900">
                {editingItem ? 'Editar Usuario' : 'Nuevo Usuario'}
              </h3>
              <button 
                onClick={() => setShowUserModal(false)}
                className="rounded-lg bg-slate-100 p-2 text-slate-600 hover:bg-slate-200"
              >
                <X size={18} />
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Nombres</label>
                <input
                  type="text"
                  value={formData.Nombres}
                  onChange={(e) => setFormData({...formData, Nombres: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Apellidos</label>
                <input
                  type="text"
                  value={formData.Apellidos}
                  onChange={(e) => setFormData({...formData, Apellidos: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Email Corporativo</label>
                <input
                  type="email"
                  value={formData.EmailCorporativo}
                  onChange={(e) => setFormData({...formData, EmailCorporativo: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Teléfono</label>
                <input
                  type="text"
                  value={formData.Telefono}
                  onChange={(e) => setFormData({...formData, Telefono: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Cargo del jefe directo</label>
                <select
                  value={formData.CargoJefeDirectoId ?? ''}
                  onChange={(e) => setFormData({...formData, CargoJefeDirectoId: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 bg-white px-4 py-2 focus:border-orange-500 focus:outline-none"
                >
                  <option value="">Sin cargo de jefe configurado</option>
                  {jobTitles.map((jobTitle) => (
                    <option key={jobTitle.Id} value={jobTitle.Id}>
                      {jobTitle.Nombre} — {jobTitle.Codigo}
                    </option>
                  ))}
                </select>
                <p className="mt-1 text-xs text-slate-500">
                  Cualquier usuario activo con este cargo podrá aprobar primero las solicitudes laborales.
                </p>
              </div>
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={formData.Estado}
                  onChange={(e) => setFormData({...formData, Estado: e.target.checked})}
                  className="rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                />
                <span className="text-sm text-slate-700">Usuario Activo</span>
              </label>
              <button
                onClick={handleSaveUser}
                className="w-full rounded-xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white hover:bg-orange-700 transition"
              >
                {editingItem ? 'Actualizar Usuario' : 'Crear Usuario'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Rol */}
      {showRoleModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="max-w-md w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
            <div className="mb-4 flex items-center justify-between">
              <h3 className="text-xl font-bold text-slate-900">
                {editingItem ? 'Editar Rol' : 'Nuevo Rol'}
              </h3>
              <button 
                onClick={() => setShowRoleModal(false)}
                className="rounded-lg bg-slate-100 p-2 text-slate-600 hover:bg-slate-200"
              >
                <X size={18} />
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
                <input
                  type="text"
                  value={formData.Nombre}
                  onChange={(e) => setFormData({...formData, Nombre: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Código</label>
                <input
                  type="text"
                  value={formData.Codigo}
                  onChange={(e) => setFormData({...formData, Codigo: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Descripción</label>
                <textarea
                  value={formData.Descripcion}
                  onChange={(e) => setFormData({...formData, Descripcion: e.target.value})}
                  rows={3}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Nivel de Acceso</label>
                <input
                  type="number"
                  value={formData.NivelAcceso}
                  onChange={(e) => setFormData({...formData, NivelAcceso: parseInt(e.target.value)})}
                  min="1"
                  max="10"
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
                <p className="mt-1 text-xs text-slate-500">1 = Básico, 10 = Administrador Total</p>
              </div>
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={formData.Estado}
                  onChange={(e) => setFormData({...formData, Estado: e.target.checked})}
                  className="rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                />
                <span className="text-sm text-slate-700">Rol Activo</span>
              </label>
              <button
                onClick={handleSaveRole}
                className="w-full rounded-xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white hover:bg-orange-700 transition"
              >
                {editingItem ? 'Actualizar Rol' : 'Crear Rol'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Permiso */}
      {showPermissionModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="max-w-md w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
            <div className="mb-4 flex items-center justify-between">
              <h3 className="text-xl font-bold text-slate-900">
                {editingItem ? 'Editar Permiso' : 'Nuevo Permiso'}
              </h3>
              <button 
                onClick={() => setShowPermissionModal(false)}
                className="rounded-lg bg-slate-100 p-2 text-slate-600 hover:bg-slate-200"
              >
                <X size={18} />
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Aplicación</label>
                <select
                  value={formData.AplicacionId}
                  onChange={(e) => setFormData({...formData, AplicacionId: parseInt(e.target.value)})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                  disabled={!!editingItem}
                >
                  {applications.map((app) => (
                    <option key={app.Id} value={app.Id}>{app.Nombre}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Módulo</label>
                <input
                  type="text"
                  value={formData.Modulo}
                  onChange={(e) => setFormData({...formData, Modulo: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Acción</label>
                <input
                  type="text"
                  value={formData.Accion}
                  onChange={(e) => setFormData({...formData, Accion: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Código Único</label>
                <input
                  type="text"
                  value={formData.CodigoUnico}
                  onChange={(e) => setFormData({...formData, CodigoUnico: e.target.value})}
                  placeholder="Ej: dashboard.ver"
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Descripción</label>
                <textarea
                  value={formData.Descripcion}
                  onChange={(e) => setFormData({...formData, Descripcion: e.target.value})}
                  rows={3}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={formData.Estado}
                  onChange={(e) => setFormData({...formData, Estado: e.target.checked})}
                  className="rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                />
                <span className="text-sm text-slate-700">Permiso Activo</span>
              </label>
              <button
                onClick={handleSavePermission}
                className="w-full rounded-xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white hover:bg-orange-700 transition"
              >
                {editingItem ? 'Actualizar Permiso' : 'Crear Permiso'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Modal de Aplicación */}
      {showApplicationModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="max-w-md w-full rounded-3xl border border-slate-200 bg-white p-6 shadow-xl">
            <div className="mb-4 flex items-center justify-between">
              <h3 className="text-xl font-bold text-slate-900">
                {editingItem ? 'Editar Aplicación' : 'Nueva Aplicación'}
              </h3>
              <button 
                onClick={() => setShowApplicationModal(false)}
                className="rounded-lg bg-slate-100 p-2 text-slate-600 hover:bg-slate-200"
              >
                <X size={18} />
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Nombre</label>
                <input
                  type="text"
                  value={formData.Nombre}
                  onChange={(e) => setFormData({...formData, Nombre: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Código</label>
                <input
                  type="text"
                  value={formData.Codigo}
                  onChange={(e) => setFormData({...formData, Codigo: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Ruta</label>
                <input
                  type="text"
                  value={formData.Ruta}
                  onChange={(e) => setFormData({...formData, Ruta: e.target.value})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Color</label>
                <input
                  type="color"
                  value={formData.Color}
                  onChange={(e) => setFormData({...formData, Color: e.target.value})}
                  className="h-10 w-full rounded-xl border border-slate-300"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Icono</label>
                <input
                  type="text"
                  value={formData.Icono}
                  onChange={(e) => setFormData({...formData, Icono: e.target.value})}
                  placeholder="Ej: layout-dashboard"
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-700">Orden</label>
                <input
                  type="number"
                  value={formData.OrdenMenu}
                  onChange={(e) => setFormData({...formData, OrdenMenu: parseInt(e.target.value) || 0})}
                  className="w-full rounded-xl border border-slate-300 px-4 py-2 focus:border-orange-500 focus:outline-none"
                />
              </div>
              <div className="flex gap-4">
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={formData.Visible}
                    onChange={(e) => setFormData({...formData, Visible: e.target.checked})}
                    className="rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                  />
                  <span className="text-sm text-slate-700">Visible en menú</span>
                </label>
                <label className="flex items-center gap-2">
                  <input
                    type="checkbox"
                    checked={formData.Estado}
                    onChange={(e) => setFormData({...formData, Estado: e.target.checked})}
                    className="rounded border-slate-300 text-orange-600 focus:ring-orange-500"
                  />
                  <span className="text-sm text-slate-700">Aplicación Activa</span>
                </label>
              </div>
              <button
                onClick={handleSaveApplication}
                className="w-full rounded-xl bg-orange-600 px-4 py-3 text-sm font-semibold text-white hover:bg-orange-700 transition"
              >
                {editingItem ? 'Actualizar Aplicación' : 'Crear Aplicación'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default SecurityPage;
