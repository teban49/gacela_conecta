// Texto con marcas simples: **negrita** y [texto](enlace)
const FORM_URL = 'https://docs.google.com/forms/d/1AkjdPURKO28mOEMOsH1E-lo3KTj2mWEz6fTF5ZDGWIE/viewform?edit_requested=true';
const NOMINA_MAIL = 'mailto:analista.nomina@flotamagdalena.com.co';

const step = {
  form: `**Ingrese al formulario de solicitud:** [👉 Ingresar al formulario](${FORM_URL})`,
  fill: '**Diligencie la información solicitada** en el formulario.',
  send: '**Envíe la solicitud** una vez haya completado todos los campos.',
  wait: '**Espere la respuesta.** El tiempo estimado de respuesta es de **hasta 8 días hábiles**. La respuesta será enviada a través de los medios corporativos establecidos.',
  doubts: `**¿Tiene dudas, aclaraciones o reclamaciones?** Comuníquese a través del correo: [analista.nomina@flotamagdalena.com.co](${NOMINA_MAIL})`,
};

const steps = (...items) => ({ type: 'steps', items });
const list = (...items) => ({ type: 'list', items });
const p = (text) => ({ type: 'p', text });

const faqCategories = [
  {
    id: 'gestion-humana',
    title: '👥 Gestión Humana / Nómina',
    questions: [
      {
        q: '📄 ¿Cómo solicitar desprendible de nómina?',
        blocks: [steps(step.form, step.fill, step.send, step.wait, step.doubts)],
      },
      {
        q: '📄 ¿Cómo solicitar certificado laboral?',
        blocks: [steps(step.form, step.fill, step.send, step.wait, step.doubts)],
      },
      {
        q: '📄 ¿Cómo solicitar certificado de ingresos y retenciones?',
        blocks: [steps(step.form, step.fill, step.send, step.wait, step.doubts)],
      },
      {
        q: '🏖️ ¿Cómo solicitar vacaciones?',
        blocks: [steps(
          step.form,
          step.fill,
          '**Adjunte el formato SOLICITUD DE VACACIONES** en PDF. El documento debe estar firmado por el solicitante y contar con la firma de aprobación del **jefe inmediato y/o administrador**. De lo contrario, la solicitud será rechazada.',
          step.send,
          step.wait,
          step.doubts,
        )],
      },
      {
        q: '📋 ¿Cómo solicitar licencia no remunerada?',
        blocks: [steps(
          step.form,
          step.fill,
          '**Adjunte el formato SOLICITUD DE PERMISO** en PDF, firmado por el empleado y con firma de aceptación del **jefe inmediato**.',
          step.send,
          step.wait,
          step.doubts,
        )],
      },
      {
        q: '📄 ¿Cuál es el proceso para presentar una renuncia voluntaria?',
        blocks: [steps(
          step.form,
          step.fill,
          '**Adjunte la carta de renuncia.**',
          step.send,
          '**Diligencie el formato PAZ Y SALVO PERSONAL** y recolecte las firmas correspondientes.',
          '**Entregue el formato físico PAZ Y SALVO PERSONAL** al jefe o coordinador de Gestión Humana.',
          step.wait,
          step.doubts,
        )],
      },
      {
        q: '💰 ¿Cómo hacer retiro de cesantías?',
        blocks: [
          p('La solicitud de cesantías se puede realizar en los siguientes casos. Tenga en cuenta los documentos requeridos y el procedimiento correspondiente según cada situación.'),
          p('**1. Arreglos locativos** — Documentos requeridos:'),
          list(
            'Carta de solicitud con el valor a retirar.',
            'Certificado de Tradición y Libertad del inmueble, no mayor a 60 días, donde figure el empleado como propietario.',
            'Contrato por la labor de mejora.',
            'Cotización de materiales (Homecenter, Easy y/o similares).',
            'Autorización de Transferencia de Recursos (aplica para Porvenir y Protección).',
          ),
          p('**2. Compra de vivienda nueva o usada**'),
          list(
            '**Vivienda usada:** promesa de compraventa autenticada por notaría.',
            '**Vivienda nueva:** promesa de compraventa emitida por la constructora.',
          ),
          p('**3. Estudios superiores (pregrado) para empleado y/o beneficiarios** — Documento requerido: recibo de pago emitido por la entidad educativa. Este proceso se realiza directamente en el fondo de cesantías, quien emite cheque de gerencia a nombre de dicha entidad.'),
          p('**Procedimiento:**'),
          steps(
            step.form,
            step.fill,
            '**Adjunte los documentos** según corresponda al caso.',
            step.send,
            '**Espere la respuesta.** El tiempo estimado de respuesta es de **hasta 8 días hábiles**.',
            step.doubts,
          ),
        ],
      },
    ],
  },
  {
    id: 'caja-pagos',
    title: '💰 Caja y Pagos',
    questions: [
      {
        q: '💵 ¿Qué debo hacer si recibo un billete falso?',
        blocks: [p('El billete falso recibido será registrado como faltante y deberá ser asumido por el **agencista responsable**.')],
      },
      {
        q: '📱 ¿Puedo recibir pagos de clientes mediante mi Nequi, Daviplata o Transfiya personal?',
        blocks: [p('**No.** Está prohibido recibir pagos de clientes mediante cuentas personales de **Nequi, Daviplata o Transfiya**.')],
      },
      {
        q: '💳 ¿Qué debo hacer si el datáfono presenta una falla?',
        blocks: [p('Debe reportar la falla a la línea de atención del proveedor. Si la atención presenta demoras, debe informar la novedad al **Área de Sistemas**.')],
      },
      {
        q: '💰 ¿Qué pasa si tengo un faltante de dinero en caja?',
        blocks: [p('Los faltantes que no tengan justificación deberán ser asumidos por el **agencista responsable**. El plazo para realizar el pago dependerá del valor del faltante.')],
      },
      {
        q: '🚗 ¿Cómo solicitar viáticos administrativos?',
        blocks: [p('Se debe diligenciar el formato **Gastos de transporte terrestre**, previamente autorizado y con las firmas del colaborador y jefe inmediato. El formato debe ser entregado al **jefe de Tesorería**.')],
      },
    ],
  },
  {
    id: 'atencion-usuario',
    title: '🚌 Atención al Usuario',
    questions: [
      {
        q: '📢 ¿Qué se considera una PQR?',
        blocks: [
          p('Una PQR corresponde a:'),
          list('**Petición**', '**Queja**', '**Reclamo**', '**Sugerencia**'),
          p('También pueden incluir felicitaciones o solicitudes de información.'),
        ],
      },
      {
        q: '⏱️ ¿Cuál es el tiempo máximo para responder una PQR?',
        blocks: [p('Los tiempos de respuesta dependerán de la política interna de la empresa y de la normatividad vigente. Se recomienda responder dentro de los tiempos establecidos y, como referencia interna, en un plazo **no mayor a 15 días** para garantizar una atención oportuna.')],
      },
      {
        q: '📝 ¿Qué información debe contener el formato de atención al usuario?',
        blocks: [
          p('Todo reporte debe incluir:'),
          list(
            'Nombre del usuario.',
            'Número de documento.',
            'Fecha y hora del viaje.',
            'Ruta.',
            'Número de tiquete o vehículo.',
            'Descripción clara de la situación.',
            'Evidencias, si existen (fotos, videos, tiquetes, etc.).',
          ),
        ],
      },
      {
        q: '💵 ¿Qué hacer cuando un usuario solicita devolución de dinero?',
        blocks: [
          p('Se debe validar:'),
          list('Estado del tiquete.', 'Políticas de cancelación.', 'Tiempo de anticipación.', 'Motivo de la solicitud.'),
          p('Posteriormente, el caso debe remitirse al **Área de Servicio al Cliente** para validación y respuesta.'),
        ],
      },
      {
        q: '🚌 ¿Qué hacer si el usuario perdió el bus?',
        blocks: [
          p('Se debe verificar:'),
          list('Hora de salida.', 'Estado del tiquete que haya quedado abierto.', 'Estado del despacho.'),
          p('Se podrá realizar cambio o reprogramación si fue avisado con anticipación y el tiquete se encuentra abierto. De lo contrario, se considera pérdida del tiquete.'),
        ],
      },
      {
        q: '😠 ¿Cómo actuar frente a un usuario molesto?',
        blocks: [list(
          'Mantener la calma.',
          'Escuchar atentamente.',
          'Evitar discusiones.',
          'Brindar alternativas de solución con información clara.',
          'Escalar el caso si es necesario.',
        )],
      },
      {
        q: '🎫 ¿Quién puede autorizar cambios de tiquetes o reprogramaciones?',
        blocks: [
          p('Únicamente el personal autorizado según las políticas de la empresa:'),
          list('Agencista autorizado.', 'Coordinador de Servicio al Cliente.', 'Gerencia Operativa, según el caso.'),
        ],
      },
      {
        q: '📢 ¿Qué hacer cuando se recibe una queja?',
        blocks: [
          p('Debe:'),
          list(
            'Proporcionar los medios para que el usuario la reporte mediante formato físico, telefónicamente o por correo.',
            'Informar inmediatamente al **Coordinador de Servicio al Cliente**.',
            'Salvaguardar los datos personales del usuario de manera confidencial.',
            'Entregar la versión de los hechos.',
            'Colaborar con la investigación interna.',
          ),
        ],
      },
      {
        q: '🏢 ¿Cuándo debe copiarse un caso a Gerencia?',
        blocks: [
          p('Cuando:'),
          list(
            'Exista riesgo jurídico.',
            'El usuario manifieste acciones legales.',
            'Se presenten accidentes o incidentes graves.',
            'Existan afectaciones reputacionales.',
            'El caso involucre reincidencias.',
          ),
        ],
      },
      {
        q: '👥 ¿Qué áreas participan en el proceso de Servicio al Cliente?',
        blocks: [list(
          'Agencias.',
          'Torre de Control.',
          'Operaciones.',
          'Conductores.',
          'Coordinador de Servicio al Cliente.',
          'Gestión Humana.',
          'Departamento Mecánico.',
        )],
      },
      {
        q: '👨‍💼 ¿Qué hacer si el usuario solicita hablar con un supervisor?',
        blocks: [p('Se debe escalar el caso de manera respetuosa y documentada al **Coordinador de Servicio al Cliente**.')],
      },
      {
        q: '⚠️ ¿Qué pasa si un colaborador no reporta una novedad?',
        blocks: [p('El incumplimiento en el reporte de novedades podrá generar llamados de atención o sanciones disciplinarias, debido a que afecta la trazabilidad y gestión del servicio.')],
      },
      {
        q: '🚌 ¿Qué hacer cuando un vehículo presenta fallas durante el recorrido?',
        blocks: [
          p('Se debe:'),
          list(
            'Reportar inmediatamente a **Torre de Control**.',
            'Activar el plan de contingencia.',
            'Informar oportunamente a los pasajeros.',
            'Gestionar la solución o transbordo, si aplica.',
          ),
        ],
      },
      {
        q: '📱 ¿Cómo deben manejarse los grupos de WhatsApp operativos?',
        blocks: [
          p('Los grupos deben utilizarse únicamente para:'),
          list('Reportes operativos.', 'Novedades.', 'Contingencias.', 'Coordinación del servicio.'),
          p('No deben utilizarse para conversaciones personales o información no relacionada con la operación.'),
        ],
      },
      {
        q: '🎒 ¿Qué hacer cuando un usuario reporta objetos perdidos?',
        blocks: [
          p('Debe registrarse:'),
          list('Fecha.', 'Ruta.', 'Número de tiquete o vehículo.', 'Descripción del objeto.'),
          p('Posteriormente, se debe validar con conductor, agencias y plataforma de objetos perdidos.'),
          p('Para realizar la entrega del objeto perdido se debe solicitar al usuario copia del **tiquete** y copia de la **cédula**, además de diligenciar el formato de **Equipaje Olvidado**.'),
        ],
      },
    ],
  },
  {
    id: 'sistemas',
    title: '💻 Sistemas y Administrativo',
    questions: [
      {
        q: '📶 ¿Cuál es el proceso para solicitar instalación de Wi-Fi en un bus?',
        blocks: [
          p('El propietario debe diligenciar y firmar el formato **Autorización Wi-Fi**, según la empresa a la que esté vinculado el vehículo.'),
          p('El formato debe ser enviado por cualquier medio al **Departamento de Sistemas**.'),
          p('Un colaborador del Departamento de Sistemas validará la información y se pondrá en contacto con el propietario o conductor para coordinar el proceso de instalación.'),
        ],
      },
      {
        q: '👤 ¿Cómo solicito la creación o eliminación de un usuario en FICS?',
        blocks: [
          p('El agencista responsable deberá realizar la solicitud mediante el aplicativo **GIS**, creando una incidencia para solicitar la creación o eliminación del usuario.'),
          p('En el detalle de la incidencia debe indicar:'),
          list('Número de cédula.', 'Nombre del usuario.', 'Agencia.', 'Empresa.'),
        ],
      },
      {
        q: '🌐 ¿Qué debo hacer si la agencia se queda sin internet?',
        blocks: [
          p('Verifique que los cables de red y equipos de conexión estén encendidos y correctamente conectados.'),
          p('Si continúa sin servicio, active los **datos móviles del celular** y utilice la opción **Zona Wi-Fi / Compartir Internet** para conectar temporalmente el computador.'),
          p('Finalmente, reporte la novedad a Sistemas indicando la **agencia y desde cuándo presenta la falla**.'),
        ],
      },
      {
        q: '🖨️ ¿Qué debo hacer si la impresora de tiquetes no imprime?',
        blocks: [
          p('Verifique que la impresora esté encendida, tenga papel y no presente luces o mensajes de error.'),
          p('Revise la conexión USB o de red y realice un reinicio de la impresora.'),
          p('Si continúa la falla, reporte a Sistemas indicando: **agencia, nombre de la impresora y mensaje de error**, si aparece.'),
        ],
      },
      {
        q: '🐌 ¿Qué debo hacer si FICS o PXP está demasiado lento?',
        blocks: [
          p('Primero, verifique si la lentitud ocurre únicamente en FICS/PXP o también en otras páginas y aplicaciones.'),
          p('Cierre programas innecesarios y, si es posible, pruebe la conexión a internet.'),
          p('Si la lentitud continúa, reporte a Sistemas indicando: **sistema afectado, equipo, usuario y hora aproximada en que comenzó**.'),
        ],
      },
      {
        q: '🔐 ¿Qué debo hacer si FICS o PXP no me permite ingresar?',
        blocks: [
          p('Verifique que esté utilizando el usuario y contraseña correctos y que la conexión a internet funcione.'),
          p('Si aparece un mensaje de error, tome una **captura de pantalla** y envíela a Sistemas junto con el usuario y la agencia.'),
          p('**Nunca comparta su contraseña.**'),
        ],
      },
      {
        q: '💻 ¿Qué debo hacer si el computador se bloquea o queda congelado?',
        blocks: [
          p('Espere unos segundos para verificar si el equipo responde.'),
          p('Si continúa bloqueado, intente cerrar la aplicación y, como última opción, reinicie el computador.'),
          p('Si el problema se presenta nuevamente, reporte a Sistemas indicando **qué estaba haciendo, qué aplicación utilizaba y con qué frecuencia ocurre**.'),
        ],
      },
      {
        q: '⚠️ ¿Qué debo hacer si aparece un error al realizar una venta?',
        blocks: [
          p('No repita la operación varias veces si existe riesgo de generar una **venta duplicada**.'),
          p('Tome una captura del mensaje, registre la hora de la operación y reporte el caso a Sistemas indicando el **número de tiquete o transacción**, si aplica.'),
        ],
      },
      {
        q: '🖨️ ¿Qué debo hacer si el tiquete sale cortado, borroso o con información incompleta?',
        blocks: [
          p('Verifique que el rollo de papel esté correctamente instalado y que la impresora tenga el papel adecuado.'),
          p('Realice una impresión de prueba.'),
          p('Si el inconveniente continúa, reporte a Sistemas adjuntando una **fotografía del tiquete y de la impresora**.'),
        ],
      },
      {
        q: '📋 ¿Qué información debo enviar al reportar una falla a Sistemas?',
        blocks: [
          p('Indique siempre:'),
          list(
            'Agencia.',
            'Equipo afectado.',
            'Usuario.',
            'Sistema o servicio con problemas.',
            'Descripción de la falla.',
            'Hora aproximada.',
            'Captura o fotografía del error, cuando sea posible.',
          ),
        ],
      },
      {
        q: '🌐 ¿Qué debo hacer si una aplicación o página no carga, pero tengo internet?',
        blocks: [
          p('Pruebe si otras páginas funcionan correctamente y cierre y vuelva a abrir la aplicación o navegador.'),
          p('Si el problema continúa, reporte a Sistemas indicando **la página o aplicación afectada y el mensaje que aparece**.'),
        ],
      },
      {
        q: '🛠️ ¿Qué debo hacer si un equipo presenta una falla que no puedo solucionar?',
        blocks: [
          p('No desarme, manipule ni realice configuraciones avanzadas en el equipo.'),
          p('Reporte la novedad al **Área de Sistemas** indicando la información del problema y espere las indicaciones del personal de soporte.'),
        ],
      },
    ],
  },
];

export default faqCategories;
