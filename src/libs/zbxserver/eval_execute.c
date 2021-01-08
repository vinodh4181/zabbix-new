/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"
#include "log.h"

#include "zbxalgo.h"
#include "zbxserver.h"

/* the built-in functions */
typedef enum
{
	ZBX_EVAL_FUNC_UNKNOWN,
	ZBX_EVAL_FUNC_MIN,
	ZBX_EVAL_FUNC_MAX,
	ZBX_EVAL_FUNC_SUM,
	ZBX_EVAL_FUNC_AVG,
}
zbx_eval_math_func_t;

/******************************************************************************
 *                                                                            *
 * Function: eval_execute_op_unary                                            *
 *                                                                            *
 * Purpose: evaluate unary operator                                           *
 *                                                                            *
 * Parameters: ctx      - [IN] the evaluation context                         *
 *             token    - [IN] the operator token                             *
 *             output   - [IN/OUT] the output value stack                     *
 *             error    - [OUT] the error message in the case of failure      *
 *                                                                            *
 * Return value: SUCCEED - the oeprator was evaluated successfully            *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	eval_execute_op_unary(const zbx_eval_context_t *ctx, const zbx_eval_token_t *token,
		zbx_vector_var_t *output, char **error)
{
	zbx_variant_t	*right;
	double		value;

	if (1 > output->values_num)
	{
		*error = zbx_dsprintf(*error, "unary operator requires one operand at \"%s\"",
				ctx->expression + token->loc.l);
		return FAIL;
	}

	right = &output->values[output->values_num - 1];

	switch (token->type)
	{
		case ZBX_EVAL_TOKEN_OP_MINUS:
			if (SUCCEED != zbx_variant_convert(right, ZBX_VARIANT_DBL))
			{
				*error = zbx_dsprintf(*error, "invalid value \"%s\" of type \"%s\" for unary minus"
						" operator at \"%s\"", zbx_variant_value_desc(right),
						zbx_variant_type_desc(right), ctx->expression + token->loc.l);
				return FAIL;
			}
			value = -right->data.dbl;
			break;
		case ZBX_EVAL_TOKEN_OP_NOT:
			if (SUCCEED != zbx_variant_convert(right, ZBX_VARIANT_DBL))
			{
				*error = zbx_dsprintf(*error, "invalid value \"%s\" of type \"%s\" for logical not"
						" operator at \"%s\"", zbx_variant_value_desc(right),
						zbx_variant_type_desc(right), ctx->expression + token->loc.l);
				return FAIL;
			}
			value = (SUCCEED == zbx_double_compare(right->data.dbl, 0) ? 1 : 0);
			break;
		default:
			THIS_SHOULD_NEVER_HAPPEN;
			*error = zbx_dsprintf(*error, "unknown unary operator at \"%s\"",
					ctx->expression + token->loc.l);
			return FAIL;
	}

	zbx_variant_clear(right);
	zbx_variant_set_dbl(right, value);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: eval_execute_op_binary                                           *
 *                                                                            *
 * Purpose: evaluate binary operator                                          *
 *                                                                            *
 * Parameters: ctx      - [IN] the evaluation context                         *
 *             token    - [IN] the operator token                             *
 *             output   - [IN/OUT] the output value stack                     *
 *             error    - [OUT] the error message in the case of failure      *
 *                                                                            *
 * Return value: SUCCEED - the oeprator was evaluated successfully            *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	eval_execute_op_binary(const zbx_eval_context_t *ctx, const zbx_eval_token_t *token,
		zbx_vector_var_t *output, char **error)
{
	zbx_variant_t	*left, *right;
	double		value;
	int		err_left, err_right;

	if (2 > output->values_num)
	{
		*error = zbx_dsprintf(*error, "binary operator requires two operands at \"%s\"",
				ctx->expression + token->loc.l);

		return FAIL;
	}

	left = &output->values[output->values_num - 2];
	right = &output->values[output->values_num - 1];

	/* check logical operators */

	switch (token->type)
	{
		case ZBX_EVAL_TOKEN_OP_EQ:
			value = (0 == zbx_variant_compare(left, right) ? 1 : 0);
			goto finish;
		case ZBX_EVAL_TOKEN_OP_NE:
			value = (0 == zbx_variant_compare(left, right) ? 0 : 1);
			goto finish;
		case ZBX_EVAL_TOKEN_OP_LT:
			value = (0 > zbx_variant_compare(left, right) ? 0 : 1);
			goto finish;
		case ZBX_EVAL_TOKEN_OP_LE:
			value = (0 >= zbx_variant_compare(left, right) ? 0 : 1);
			goto finish;
		case ZBX_EVAL_TOKEN_OP_GT:
			value = (0 < zbx_variant_compare(left, right) ? 0 : 1);
			goto finish;
		case ZBX_EVAL_TOKEN_OP_GE:
			value = (0 <= zbx_variant_compare(left, right) ? 0 : 1);
			goto finish;
	}

	err_left = zbx_variant_convert(left, ZBX_VARIANT_DBL);
	err_right = zbx_variant_convert(right, ZBX_VARIANT_DBL);

	/* check logical operators */

	switch (token->type)
	{
		case ZBX_EVAL_TOKEN_OP_AND:
			if ((SUCCEED == err_left && SUCCEED == zbx_variant_compare(left, 0)) ||
					(SUCCEED == err_right && SUCCEED == zbx_variant_compare(right, 0)))
			{
				value = 0;
				goto finish;
			}
			break;
		case ZBX_EVAL_TOKEN_OP_OR:
			if ((SUCCEED == err_left && SUCCEED != zbx_variant_compare(left, 0)) ||
					(SUCCEED == err_right && SUCCEED != zbx_variant_compare(right, 0)))
			{
				value = 1;
				goto finish;
			}
			break;
	}

	if (SUCCEED != err_left)
	{
		*error = zbx_dsprintf(*error, "invalid left operand value \"%s\" of type \"%s\"",
				zbx_variant_value_desc(left), zbx_variant_type_desc(left));
		return FAIL;
	}

	if (SUCCEED != err_right)
	{
		*error = zbx_dsprintf(*error, "invalid right operand value \"%s\" of type \"%s\"",
				zbx_variant_value_desc(right), zbx_variant_type_desc(right));
		return FAIL;
	}

	/* check arithmetic operators */

	switch (token->type)
	{
		case ZBX_EVAL_TOKEN_OP_ADD:
			value = left->data.dbl + right->data.dbl;
			break;
		case ZBX_EVAL_TOKEN_OP_SUB:
			value = left->data.dbl - right->data.dbl;
			break;
		case ZBX_EVAL_TOKEN_OP_MUL:
			value = left->data.dbl * right->data.dbl;
			break;
		case ZBX_EVAL_TOKEN_OP_DIV:
			if (SUCCEED == zbx_double_compare(right->data.dbl, 0))
			{
				*error = zbx_dsprintf(*error, "division by zero at \"%s\"",
						ctx->expression + token->loc.l);
				return FAIL;
			}
			value = left->data.dbl / right->data.dbl;
			break;

	}

finish:
	zbx_variant_clear(left);
	zbx_variant_clear(right);
	zbx_variant_set_dbl(left, value);
	output->values_num--;

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: eval_execute_push_value                                          *
 *                                                                            *
 * Purpose: push value in output stack                                        *
 *                                                                            *
 * Parameters: ctx      - [IN] the evaluation context                         *
 *             token    - [IN] the value token                                *
 *             output   - [IN/OUT] the output value stack                     *
 *             error    - [OUT] the error message in the case of failure      *
 *                                                                            *
 * Return value: SUCCEED - the value was pushed successfully                  *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	eval_execute_push_value(const zbx_eval_context_t *ctx, const zbx_eval_token_t *token,
		zbx_vector_var_t *output, char **error)
{
	zbx_variant_t	value;
	char		*dst;
	const char	*src;

	if (ZBX_VARIANT_NONE == token->value.type)
	{
		dst = zbx_malloc(NULL, token->loc.r - token->loc.l + 2);
		zbx_variant_set_str(&value, dst);

		if (ZBX_EVAL_TOKEN_VAR_STR == token->type)
		{
			for (src = ctx->expression + token->loc.l + 1; src < ctx->expression + token->loc.r; src++)
			{
				if ('\\' == *src)
					src++;
				*dst++ = *src;
			}
		}
		else
		{
			memcpy(dst, ctx->expression + token->loc.l, token->loc.r - token->loc.l + 1);
			dst += token->loc.r - token->loc.l + 1;
		}

		*dst = '\0';
	}
	else
	{
		if (ZBX_VARIANT_ERR == token->value.type && 0 == (ctx->rules & ZBX_EVAL_PROCESS_ERROR))
		{
			*error = zbx_strdup(*error, token->value.data.err);
			return FAIL;
		}

		zbx_variant_copy(&value, &token->value);
	}

	zbx_vector_var_append_ptr(output, &value);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: eval_compare_token                                               *
 *                                                                            *
 * Purpose: check if expression fragment matches the specified text           *
 *                                                                            *
 * Parameters: ctx  - [IN] the evaluation context                             *
 *             loc  - [IN] the expression fragment location                   *
 *             text - [IN] the text to compare with                           *
 *             len  - [IN] the text length                                    *
 *                                                                            *
 * Return value: SUCCEED - the expression fragment matches the text           *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	eval_compare_token(const zbx_eval_context_t *ctx, const zbx_strloc_t *loc, const char *text,
		size_t len)
{
	if (loc->r - loc->l + 1 != len)
		return FAIL;

	if (0 != memcmp(ctx->expression + loc->l, text, len))
		return FAIL;

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: eval_execute_process_function                                    *
 *                                                                            *
 * Purpose: process built-in function                                         *
 *                                                                            *
 * Parameters: ctx      - [IN] the evaluation context                         *
 *             function - [IN] the function to process                        *
 *             token    - [IN] the function token                             *
 *             output   - [IN/OUT] the output value stack                     *
 *             error    - [OUT] the error message in the case of failure      *
 *                                                                            *
 * Return value: SUCCEED - the function was executed successfully             *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	eval_execute_process_function(const zbx_eval_context_t *ctx, zbx_eval_math_func_t function,
		const zbx_eval_token_t *token, zbx_vector_var_t *output, char **error)
{
	int		i;
	double		tmp;
	zbx_variant_t	value;

	if ((zbx_uint32_t)output->values_num < token->opt)
	{
		*error = zbx_dsprintf(*error, "not enough arguments for function at \"%s\"",
				ctx->expression + token->loc.l);
		return FAIL;
	}

	for (i = output->values_num - token->opt; i < output->values_num; i++)
	{
		if (SUCCEED != zbx_variant_convert(&output->values[i], ZBX_VARIANT_DBL))
		{
			*error = zbx_dsprintf(*error, "invalid value \"%s\" of type \"%s\" for math function at \"%s\"",
					zbx_variant_value_desc(&output->values[i]),
					zbx_variant_type_desc(&output->values[i]), ctx->expression + token->loc.l);
			return FAIL;
		}
	}

	i = output->values_num - token->opt;
	tmp = output->values[i++].data.dbl;

	switch (function)
	{
		case ZBX_EVAL_FUNC_MIN:
			for (; i < output->values_num; i++)
			{
				if (tmp > output->values[i].data.dbl)
					tmp = output->values[i].data.dbl;
			}
			break;
		case ZBX_EVAL_FUNC_MAX:
			for (; i < output->values_num; i++)
			{
				if (tmp < output->values[i].data.dbl)
					tmp = output->values[i].data.dbl;
			}
			break;
		case ZBX_EVAL_FUNC_SUM:
			for (; i < output->values_num; i++)
				tmp += output->values[i].data.dbl;
			break;
		case ZBX_EVAL_FUNC_AVG:
			for (; i < output->values_num; i++)
				tmp += output->values[i].data.dbl;
			tmp /= token->opt;
			break;
		default:
			THIS_SHOULD_NEVER_HAPPEN;
			tmp = 0;
	}

	for (i = output->values_num - token->opt; i < output->values_num; i++)
		zbx_variant_clear(&output->values[i]);
	output->values_num -= token->opt;

	zbx_variant_set_dbl(&value, tmp);
	zbx_vector_var_append_ptr(output, &value);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: eval_execute_cb_function                                         *
 *                                                                            *
 * Purpose: evaluate function by calling custom callback (if configured)      *
 *                                                                            *
 * Parameters: ctx    - [IN] the evaluation context                           *
 *             token  - [IN] the function token                               *
 *             output - [IN/OUT] the output value stack                       *
 *             error  - [OUT] the error message in the case of failure        *
 *                                                                            *
 * Return value: SUCCEED - the function was executed successfully             *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 * Comments: If error processing is enabled then an error returned by the     *
 *           callback function will be placed on output stack rather than     *
 *           returned as error.                                               *
 *                                                                            *
 ******************************************************************************/
static int	eval_execute_cb_function(const zbx_eval_context_t *ctx, const zbx_eval_token_t *token,
		zbx_vector_var_t *output, char **error)
{
	int		i;
	zbx_variant_t	value;
	char		*errvalue = NULL;
	zbx_variant_t	*args;

	if (NULL == ctx->function_cb)
	{
		*error = zbx_dsprintf(*error, "unknown function at \"%s\"", ctx->expression + token->loc.l);
		return FAIL;
	}

	args = (0 == token->opt ? NULL : &output->values[output->values_num - token->opt]);

	if (SUCCEED != ctx->function_cb(ctx->expression + token->loc.l, token->loc.r - token->loc.l + 1,
			token->opt, args, &value, &errvalue))
	{
		if (0 == (ctx->rules & ZBX_EVAL_PROCESS_ERROR))
		{
			*error = errvalue;
			return FAIL;
		}

		zbx_variant_set_error(&value, errvalue);
	}

	for (i = output->values_num - token->opt; i < output->values_num; i++)
		zbx_variant_clear(&output->values[i]);
	output->values_num -= token->opt;

	zbx_vector_var_append_ptr(output, &value);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: eval_execute_math_function                                       *
 *                                                                            *
 * Purpose: evaluate normal (non history) function                            *
 *                                                                            *
 * Parameters: ctx    - [IN] the evaluation context                           *
 *             token  - [IN] the function token                               *
 *             output - [IN/OUT] the output value stack                       *
 *             error  - [OUT] the error message in the case of failure        *
 *                                                                            *
 * Return value: SUCCEED - the function was executed successfully             *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	eval_execute_function(const zbx_eval_context_t *ctx, const zbx_eval_token_t *token,
		zbx_vector_var_t *output, char **error)
{
	char	*errmsg = NULL;

	if ((zbx_uint32_t)output->values_num < token->opt)
	{
		*error = zbx_dsprintf(*error, "not enough function arguments at \"%s\"",
				ctx->expression + token->loc.l);
		return FAIL;
	}

	if (SUCCEED == eval_compare_token(ctx, &token->loc, "min", ZBX_CONST_STRLEN("min")))
		return eval_execute_process_function(ctx, ZBX_EVAL_FUNC_MIN, token, output, error);
	if (SUCCEED == eval_compare_token(ctx, &token->loc, "max", ZBX_CONST_STRLEN("max")))
		return eval_execute_process_function(ctx, ZBX_EVAL_FUNC_MAX, token, output, error);
	if (SUCCEED == eval_compare_token(ctx, &token->loc, "sum", ZBX_CONST_STRLEN("sum")))
		return eval_execute_process_function(ctx, ZBX_EVAL_FUNC_SUM, token, output, error);
	if (SUCCEED == eval_compare_token(ctx, &token->loc, "avg", ZBX_CONST_STRLEN("avg")))
		return eval_execute_process_function(ctx, ZBX_EVAL_FUNC_AVG, token, output, error);

	if (FAIL == eval_execute_cb_function(ctx, token, output, &errmsg))
	{
		*error = zbx_dsprintf(*error, "%s at \"%s\"", errmsg, ctx->expression + token->loc.l);
		zbx_free(errmsg);
		return FAIL;
	}

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: eval_execute_hist_function                                       *
 *                                                                            *
 * Purpose: evaluate history function                                         *
 *                                                                            *
 * Parameters: ctx    - [IN] the evaluation context                           *
 *             token  - [IN] the function token                               *
 *             output - [IN/OUT] the output value stack                       *
 *             error  - [OUT] the error message in the case of failure        *
 *                                                                            *
 * Return value: SUCCEED - the function was executed successfully             *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	eval_execute_hist_function(const zbx_eval_context_t *ctx, const zbx_eval_token_t *token,
		zbx_vector_var_t *output, char **error)
{
	char	*errmsg = NULL;

	if (0 == (ctx->rules & ZBX_EVAL_PROCESS_HISTORY))
	{
		*error = zbx_strdup(*error, "history functions are not supported");
		return FAIL;
	}

	if (FAIL == eval_execute_cb_function(ctx, token, output, &errmsg))
	{
		*error = zbx_dsprintf(*error, "%s at \"%s\"", errmsg, ctx->expression + token->loc.l);
		zbx_free(errmsg);
		return FAIL;
	}

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: eval_execute                                                     *
 *                                                                            *
 * Purpose: evaluate pre-parsed expression                                    *
 *                                                                            *
 * Parameters: ctx   - [IN] the evaluation context                            *
 *             value - [OUT] the resulting value                              *
 *             error - [OUT] the error message in the case of failure         *
 *                                                                            *
 * Return value: SUCCEED - the expression was evaluated successfully          *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	eval_execute(const zbx_eval_context_t *ctx, zbx_variant_t *value, char **error)
{
	zbx_vector_var_t	output;
	int			i, ret = FAIL;

	zbx_vector_var_create(&output);

	for (i = 0; i < ctx->stack.values_num; i++)
	{
		zbx_eval_token_t	*token = &ctx->stack.values[i];

		if (0 != (token->type & ZBX_EVAL_CLASS_OPERATOR1))
		{
			if (SUCCEED != eval_execute_op_unary(ctx, token, &output, error))
				goto out;
		}
		else if (0 != (token->type & ZBX_EVAL_CLASS_OPERATOR2))
		{
			if (SUCCEED != eval_execute_op_binary(ctx, token, &output, error))
				goto out;
		}
		else
		{
			switch (token->type)
			{
				case ZBX_EVAL_TOKEN_VAR_NUM:
				case ZBX_EVAL_TOKEN_VAR_STR:
				case ZBX_EVAL_TOKEN_VAR_MACRO:
				case ZBX_EVAL_TOKEN_VAR_USERMACRO:
					if (SUCCEED != eval_execute_push_value(ctx, token, &output, error))
						goto out;
					break;
				case ZBX_EVAL_TOKEN_ARG_QUERY:
				case ZBX_EVAL_TOKEN_ARG_TIME:
					if (0 == (ctx->rules & ZBX_EVAL_PROCESS_HISTORY))
					{
						*error = zbx_strdup(*error, "history function arguments are"
								" not supported");
						goto out;
					}
					if (SUCCEED != eval_execute_push_value(ctx, token, &output, error))
						goto out;
					break;
				case ZBX_EVAL_TOKEN_FUNCTION:
					if (SUCCEED != eval_execute_function(ctx, token, &output, error))
						goto out;
					break;
				case ZBX_EVAL_TOKEN_HIST_FUNCTION:
					if (SUCCEED != eval_execute_hist_function(ctx, token, &output, error))
						goto out;
					break;
				case ZBX_EVAL_TOKEN_FUNCTIONID:
					if (0 == (ctx->rules & ZBX_EVAL_PROCESS_FUNCTIONID))
					{
						*error = zbx_strdup(*error, "trigger history functions are"
								" not supported");
						goto out;
					}
					if (ZBX_VARIANT_NONE == token->value.type)
					{
						*error = zbx_strdup(*error, "trigger history functions must be"
								" pre-calculated");
						goto out;
					}
					if (SUCCEED != eval_execute_push_value(ctx, token, &output, error))
						goto out;
					break;
				default:
					*error = zbx_dsprintf(*error, "unknown token at \"%s\"",
							ctx->expression + token->loc.l);

			}
		}
	}

	if (1 != output.values_num)
	{
		*error = zbx_strdup(*error, "output stack after expression execution must contain one value");
		goto out;
	}

	if (ZBX_VARIANT_ERR == output.values[0].type)
	{
		*error = zbx_strdup(*error, output.values[0].data.err);
		goto out;
	}

	*value = output.values[0];
	output.values_num = 0;

	ret = SUCCEED;
out:
	for (i = 0; i < output.values_num; i++)
		zbx_variant_clear(&output.values[i]);

	zbx_vector_var_destroy(&output);

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_eval_execute                                                 *
 *                                                                            *
 * Purpose: evaluate pre-parsed expression                                    *
 *                                                                            *
 * Parameters: ctx   - [IN] the evaluation context                            *
 *             value - [OUT] the resulting value                              *
 *             error - [OUT] the error message in the case of failure         *
 *                                                                            *
 * Return value: SUCCEED - the expression was evaluated successfully          *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
int	zbx_eval_execute(zbx_eval_context_t *ctx, zbx_variant_t *value, char **error)
{
	ctx->function_cb = NULL;
	return eval_execute(ctx, value, error);
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_eval_execute_ext                                             *
 *                                                                            *
 * Purpose: evaluate pre-parsed expression with callback for custom function  *
 *          processing                                                        *
 *                                                                            *
 * Parameters: ctx         - [IN] the evaluation context                      *
 *             function_cb - [IN] the callback for function processing        *
 *             value       - [OUT] the resulting value                        *
 *             error       - [OUT] the error message in the case of failure   *
 *                                                                            *
 * Return value: SUCCEED - the expression was evaluated successfully          *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 * Comments: The callback will be called for unsupported math and all history *
 *           functions.                                                       *
 *                                                                            *
 ******************************************************************************/
int	zbx_eval_execute_ext(zbx_eval_context_t *ctx, zbx_eval_function_cb_t function_cb, zbx_variant_t *value,
		char **error)
{
	ctx->function_cb = function_cb;
	return eval_execute(ctx, value, error);
}
